
# lpp-to-sql

Ce repository a pour but de transformer Le fichier NX trouvé sur le site
de la sécurité sociale en données SQL exploitable avec les technologies modernes



# Utilisation de l'outil

Voici comment construire un fichier SQL à partir des données du fichier

1. [Télécharger le fichier NX/DAT](http://www.codage.ext.cnamts.fr/f_mediam/fo/tips/LPPTOT696.zip)
2. Placer le fichier décompréssé dans ce répertoire (celui ci devrat être nommé `LPPTOT696`)
3. Éxecuter `php main.php`
4. Récupérer le fichier dans `./archives/<timestamp>/LPPTOT696.sql`


# Étude du fichier NX/DAT

**Note: il est préférable de lire directement ce fichier avec un éditeur de texte pour mieux observer l'alignement des données**
**Sur VSC, appuyez sur `Alt+Z` pour (dés)activer le mode de fin de ligne**

Voici un extrait de ce que nous pouvons trouver dans le fichier une fois celui-ci traité,


```txt
10101016364080      SET POUR PLAIE POST-OP,SUTURE NON INFECTEE, [ 5 - 10 CM[ ,3 SOIN,TETRA MEDICAL                              101020100000000000AN000001000003000001000005000001000000000000000000000000000000000028000000    1368908                         11001012019060100000000PANN20190524201905240000000091013001150115013511000136000000000000000000000910000000                     199010100004
10101011100028      SOLUTION A USAGE OPHTALMIQUE, SANTEN, CATIONORM, BOITE DE 30 UNIDOSES, 0,4 ML                               101020100000000000AN000001000001000007000005000003000000000000000000000000000000000020000000                                    11001012019072900000000AADN20190716201907160000000041513001150120012001000136000000000000000000000000000000                     199010100004
10101011100034      SOLUTION POUR PULVERISATIONS ENDO-BUCCALES, EISAI, AEQUASYAL, 400 DOSES                                     101020100000000000AO000001000001000007000005000002000000000000000000000000000000000001000000                                    11001012021100100000000AADN20210604202106040000000076413001150120012001000136000000000000000000000764000000                     11001022020100120210930AADN20171124201711240000000080413001150120012001000136000000000000000000000804000000                     11001032019100120200930AADN20171124201711240000000091713001150120012001000136000000000000000000000917000000                     11001042018100120190930AADN20171124201711240000000103013001150120012001000136000000000000000000001030000000                     199010100007
10101011100040      PPC, APNEE SOMMEIL, PATIENT TELEOBSERVE, FORFAIT HEBDO 9.2.                                                 101020100000000000SO000001000005000000000000000000000000000000000000000000000000000003000000                                    11001012013111220140213AARN20131022201310300000000210013001150120012001000136000000000000000000002100000000                     199010100004
10101011100070      PERFUSION, DIFFUSEUR, <24 H, SERINGUE 50 ML, BAXTER, SV 5, REF C 1073 K                                     101020120050329000AO000001000001000002000002000003000000000000000000000000000000000023000000    103D01.1                        11001012003090800000000MADN20030626200309060000000320113001150120012001000136000000000000000000000000000000                     199010100004
10101011100086      ALIMENT SANS GLUTEN, BISCUITS, >OU= 250 G ET < 300 G                                                        101020100000000000AO000001000001000005000001000001000000000000000000000000000000000332000000                                    11001012022010100000000RJTN20211209202112090000000031813001150120012001000136000000000000000000000000000000                     11001022004040320211231GLUN20040325200404010000000031813001150120012001000136000000000000000000000000000000                     199010100005
10101011100229      FRA-38, VENTILATION ASSISTEE, < 12 HEURES + OXYGENO OLT 1.31 INVACARE PLATINUM 9                            101020100000000000AO000001000001000001000002000004000001000000000000000000000000000054000000                                    11001012015060100000000AARO20150514201505140000000933913001150120012001000136000000000000000000009339000000                     199010100004
10101011100235      COLOSTOMIE, SET POUR IRRIGATION COLIQUE                                                                     101020100000000000AN000001000001000004000001000009000003000001000000000000000000000001000000                                    11001012021070100000000RJTN20201126202012050000000483513001150120012001000136000000000000000000004835000000                     11001022019070120210630MADN20190628201906290000000483513001150120012001000136000000000000000000004835000000                     199010100005
10101011100241      NUT. ORALE, ADULTE, MEL. POLY. NORMOPROT. HYPERENERG >= 125 ET <= 150, B/4.                                 101020100000000000AO000001000001000005000001000002000001000001000000000000000000000001000000                                    11001012021070100000000RJTN20201126202012050000000047313001150120012001000136000000000000000000000473000000                     11001022020010120210630MADN20190510201905100000000047313001150120012001000136000000000000000000000473000000                     11001032019060120191231MADN20190507201905100000000047313001150120012001000136000000000000000000000473000000                     199010100006
10101011100287      PERFUSION, DIFFUSEUR, <24 H, SERINGUE 50 ML, ZAMBON, H.P ECLIPSE REF. 400.100                               101020120050329000AO000001000001000002000002000003000000000000000000000000000000000061000000    103D01.1                        11001012003090800000000MADN20030626200309060000000320113001150120012001000136000000000000000000000000000000                     199010100004
```

Dans sont état original, le fichier n'a pas de retour à la ligne, il faut donc séparer les produits entre eux.

De ce qui a été observé, chaque tuple de donnée:
1. Commence par `/1010101XXXXXXX/`
2. Fini par `/19901010XXXX/` (Théorie: XXXX est un nombre entier à quatre chiffre dénombrant
le nombre de sections de données du tuple, moins 1)

## 1ere chaine - Code

Le numéro du produit, précédé de `1010101`

```txt
10101016364080
```

## 2eme chaine (NOM + FABRICANT)
```txt
SET POUR PLAIE POST-OP,SUTURE NON INFECTEE, [ 5 - 10 CM[ ,3 SOIN,TETRA MEDICAL
```

## 3eme chaine (Données Radiation + ???)
```txt
101020120050329000AO000001000001000002000002000003000000000000000000000000000000000023000000
```

## 4eme chaine (ANCIEN CODE)

**OPTIONNEL, parfois celui-ci n'apparait pas**
```txt
1368908
```

## 5eme chaine:

Celle-ci est la plus complexe, elle représente une période ou le produit a été en vente,
il se peut donc qu'un produit/service ait plusieurs lignes comme celles-ci

| Sample                                                                                                        | Positions | Description |
|---------------------------------------------------------------------------------------------------------------|-----------|-------------|
| `11001012021100100000000AADN20210604202106040000000076413001150120012001000136000400000000000000000764000000` | 000 - 107 | FULL LINE |
| `1100101----------------------------------------------------------------------------------------------------` | 000 - 007 | ID |
| `-------20211001--------------------------------------------------------------------------------------------` | 007 - 015 | Début Validité |
| `---------------00000000------------------------------------------------------------------------------------` | 015 - 023 | Fin validité |
| `-----------------------AAD---------------------------------------------------------------------------------` | 023 - 026 | Type |
| `--------------------------N--------------------------------------------------------------------------------` | 026 - 027 | ?? Entente préalable |
| `---------------------------20210604------------------------------------------------------------------------` | 027 - 035 | Date J.O (Journal Officiel) |
| `-----------------------------------20210604----------------------------------------------------------------` | 035 - 043 | Date Arrêté |
| `-------------------------------------------00000000764-----------------------------------------------------` | 043 - 054 | Prix (2 décimales) |
| `------------------------------------------------------1300-------------------------------------------------` | 054 - 058 | Majoration GUADELOUPE (3 décimales) |
| `----------------------------------------------------------1150---------------------------------------------` | 058 - 062 | Majoration Martinique (3 décimales) |
| `--------------------------------------------------------------1200-----------------------------------------` | 062 - 066 | Majoration Guyane (3 décimales) |
| `------------------------------------------------------------------1200-------------------------------------` | 066 - 070 | Majoration Réunion (3 décimales) |
| `----------------------------------------------------------------------1000---------------------------------` | 070 - 074 | ?? Indication |
| `--------------------------------------------------------------------------1360-----------------------------` | 074 - 078 | Majoration Mayotte (3 décimales) |
| `------------------------------------------------------------------------------004--------------------------` | 078 - 081 | Nb remboursement max |
| `---------------------------------------------------------------------------------000000000-----------------` | 081 - 090 | ??? Plusieurs Sections ? |
| `------------------------------------------------------------------------------------------00000000764------` | 090 - 101 | Prix Unitaire réglementé |
| `-----------------------------------------------------------------------------------------------------000000` | 101 - 107 | ?? Fin du prix (nombre) ? |

## 6eme chaine:

Fin du champs produit

```
19901010XXXX
```



# Documentation

Les tables SQL peuvent être trouvé dans le répertoire `db`, celles-ci suivent le contenu de chaque produit

# Code

Une classe `Amélie` a été créée pour traiter ce genre de fichier, il est disponible dans `src`,
voici comment l'utiliser


```php
// Créer le parser
$parser = new Amelie("./LPPTOT696");

// Traite le fichier
$parser->parse();

// Construit le script SQL
$parser->build("./LPPTOT696.sql");
```

## Performances

Le système utilisé pour écrire les données dans `build()` est un système à double buffer: un buffer pour les produits,
et un pour les prix, ceux-ci sont régulièrement "vidés" dans le fichier script

Ce système à été choisis car:
- stocker toutes les données puis les écrire provoquait des bugs de mémoire
- écrire toutes des données à chaque produit/prix est beaucoup trop violent pour un disque dur, cela réduit énormément les performances