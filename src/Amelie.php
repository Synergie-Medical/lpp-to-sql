<?php

class Amelie
{

    ########################################
    # CONSTANTS
    ########################################

    const FIELDS = [
        "secuId"          => ["enabled"=>true,  "pos"=>[00, 07]],
        "validityStart"   => ["enabled"=>true,  "pos"=>[07, 15], "map"=>"Amelie::toSQLDate"],
        "validityEnd"     => ["enabled"=>true,  "pos"=>[15, 23], "map"=>"Amelie::toSQLDate"],
        "type"            => ["enabled"=>true,  "pos"=>[23, 26]],
        "entente"         => ["enabled"=>false, "pos"=>[26, 27]],
        "journalDate"     => ["enabled"=>true,  "pos"=>[27, 35], "map"=>"Amelie::toSQLDate"],
        "orderDate"       => ["enabled"=>true,  "pos"=>[35, 43], "map"=>"Amelie::toSQLDate"],
        "price"           => ["enabled"=>true,  "pos"=>[43, 54], "dec"=>2],
        "majorGuadeloupe" => ["enabled"=>true,  "pos"=>[54, 58], "dec"=>3],
        "majorMartinique" => ["enabled"=>true,  "pos"=>[58, 62], "dec"=>3],
        "majorGuyane"     => ["enabled"=>true,  "pos"=>[62, 66], "dec"=>3],
        "majorReunion"    => ["enabled"=>true,  "pos"=>[66, 70], "dec"=>3],
        "indic"           => ["enabled"=>false, "pos"=>[70, 74]],
        "majorMayotte"    => ["enabled"=>true,  "pos"=>[74, 78], "dec"=>3],
        "maxRefund"       => ["enabled"=>true,  "pos"=>[78, 81], "dec"=>0],
        "unknown"         => ["enabled"=>false, "pos"=>[81, 90]],
        "unitPrice"       => ["enabled"=>true,  "pos"=>[90, 101], "dec"=>2],
        "unknownEnd"      => ["enabled"=>false, "pos"=>[101, 107]]
    ];





    ########################################
    # UTILITARIES
    ########################################

    public static function parseDecimal(string $number, int $decimal=0)
    {
        if ($decimal == 0) return intval($number);

        $commaPosition = strlen($number)-$decimal;
        $number = substr($number, 0, $commaPosition) . "." . substr($number, $commaPosition);
        return floatval($number);
    }


    public static function toSQLDate(string $date)
    {
        if ($date === "00000000") return null;
        return substr($date, 0, 4) . "-" . substr($date, 4, 2) . "-" . substr($date, 6, 2);
    }

    public static function fixUTF8(string $value)
    {
        return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
    }


    ########################################
    # IMPLEMENTATION
    ########################################


    protected $path = null;
    protected $chunks = [];
    protected $doDebug = false;




    protected function extractChunkPrice(stdClass &$chunk)
    {
        $chunk->prices = [];
        $price = [];

        for ($i=0; $i<count($chunk->raw); $i++)
        {
            $price = new stdClass();
            $price->parent = $chunk->ref;
            if (strlen($chunk->raw[$i]) !== 107)
                continue;

            foreach (self::FIELDS as $field => $settings)
            {
                if (!$settings["enabled"]) continue;
                $pos = $settings["pos"];
                $value = substr($chunk->raw[$i], $pos[0], $pos[1]-$pos[0]);

                if (isset($settings["dec"]))
                    $value = self::parseDecimal($value, $settings["dec"]);

                if ($callback = $settings["map"]??false)
                    $value = $callback($value);

                $price->$field = $value;
            }

            $chunk->prices[] = $price;
        }
    }






    public function __construct(string $path, bool $debug=false)
    {
        if (!is_file($path))
            throw new InvalidArgumentException("[$path] file does not exists !");

        $this->doDebug= $debug;
        $this->path = $path;
    }










    public function parse()
    {
        $content = file_get_contents($this->path);
        $content = substr($content, 128);
        $content = preg_replace("/ \b(19901010\d+)\b /", "$1;", $content);
        $content = explode(";", $content);

        $content = array_map("trim", $content);
        $content = array_filter($content, fn($e)=>preg_match("/^1010101\d{7}.*19901010\d+$/", $e));

        $content = array_map(function($elem){
            return [
                trim(substr($elem, 0, 14)),
                trim(substr($elem, 20, 85)),
                ...explode(";", preg_replace("/ {2,}/", ";", substr($elem, 85)))
            ];
        }, $content);

        $chunks = $content;

        unset($content);

        $size = count($chunks);
        for ($i=0; $i<$size; $i++)
        {
            if ($this->doDebug && ($i % floor($size/10) == 0))
                echo "Parsing - ". ceil((100*$i)/$size). "%".PHP_EOL;

            $chunk = &$chunks[$i];

            $new = new stdClass();
            $new->raw = $chunk;
            $chunk = $new;

            $chunk->ref = substr($chunk->raw[0], 7);
            $chunk->name = self::fixUTF8($chunk->raw[1]); // Important, certaines lignes ont des caractères du style '°'
            $chunk->old_ref = (strlen($chunk->raw[4]) == 7) ? $chunk->raw[4] : null;
            $this->extractChunkPrice($chunk);
            unset($chunk->raw);
        }

        $this->chunks = $chunks;
    }









    public function build(string $filename=null)
    {
        $filename = $filename ?? (uniqid().".sql");


        file_put_contents($filename, "SET FOREIGN_KEY_CHECKS = 0;");

        $codeBuffer = [];
        $priceBuffer = [];

        $size = count($this->chunks);


        $flush = function($filename, &$codeBuffer, &$priceBuffer){
            file_put_contents($filename,
            "INSERT IGNORE INTO lpp_code (ref, old_ref, name) VALUES \n". join(",\n", $codeBuffer) . "; \n\n".
            "INSERT IGNORE INTO lpp_code_price (
                fk_code, type, secu_id,
                validity_start, validity_end,
                jo_date, order_date,
                price, unit_price,
                major_guadeloupe, major_martinique, major_guyane, major_reunion, major_mayotte,
                max_refund
            ) VALUES \n" . join(",\n", $priceBuffer)
            .";\n\n" , FILE_APPEND);
            $codeBuffer = [];
            $priceBuffer = [];
        };

        for ($i=0; $i<$size; $i++)
        {
            if ($this->doDebug && ($i % floor($size/10) == 0))
                echo "Writing - ". ceil((100*$i)/$size). "%".PHP_EOL;

            $code = $this->chunks[$i];

            $codeBuffer[] = "('".
                $code->ref."', ".
                ($code->old_ref == null ? "NULL" :  "'".$code->old_ref."'").",'".
                str_replace("'", "''", $code->name)."'".
            ")";

            foreach ($code->prices as &$price)
            {
                $priceBuffer[] = "(".
                    "'".($price->parent)."'".
                    ",'".($price->type)."'".
                    ",'".($price->secuId)."'".
                    ",'".($price->validityStart)."'".
                    "," .($price->validityEnd == null ? "NULL" :  "'".$price->validityEnd."'").
                    ",'".($price->journalDate)."'".
                    ",'".($price->orderDate)."'".
                    ",'".($price->price)."'".
                    ",'".($price->unitPrice)."'".
                    ",'".($price->majorGuadeloupe)."'".
                    ",'".($price->majorMartinique)."'".
                    ",'".($price->majorGuyane)."'".
                    ",'".($price->majorReunion)."'".
                    ",'".($price->majorMayotte)."'".
                    ",'".($price->maxRefund)."'".
                ")";
            }

            if (count($codeBuffer)+count($priceBuffer) >= 1000)
                $flush($filename, $codeBuffer, $priceBuffer);
        }
        $flush($filename, $codeBuffer, $priceBuffer);

        file_put_contents($filename,
"DELETE FROM lpp_code_price WHERE fk_code NOT IN (select ref FROM lpp_code);

SET FOREIGN_KEY_CHECKS = 1;


UPDATE lpp_code, organization
SET fk_provider = organization.id
WHERE RIGHT(lpp_code.name, LENGTH(organization.name)) = UPPER(organization.name)
AND organization.fk_type = 4
;

"
    , FILE_APPEND);
    }
}