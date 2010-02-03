/************************************************************
 Ascodocpsy
 Load.js
 Ajout/Modification d'une notice
 Gestion de l'aide et contrôles de saisie
 Affichage des index d'aide à la saisie (tables de lookup)
 ************************************************************/

/************************************************************
 Affichage des index d'aide à la saisie (tables de lookup)
 ************************************************************/
jQuery(document).ready(
    function()
    {
        var title="Un index d'aide à la saisie est disponible pour ce champ.";
        
        jQuery('textarea[@name=Aut]').autocomplete
        (
            'Lookup?table=auteurs&value=%s',
            {
                title: title
            }
        );

        jQuery('textarea[@name=DipSpe]').autocomplete
        (
            'Lookup?table=diplomes&value=%s',
            {
                title: title
            }
        );

        jQuery('input[@name=CongrLie]').autocomplete
        (
            'Lookup?table=villes&value=%s',
            {
                title: title
            }
        );

        jQuery('textarea[@name=Edit]').autocomplete
        (
            'Lookup?table=editeurs&value=%s',
            {
                title: title
            }
        );

        jQuery('textarea[@name=Lieu]').autocomplete
        (
            'Lookup?table=lieux&value=%s',
            {
                title: title
            }
        );

        jQuery('textarea[@name=Col]').autocomplete
        (
            'Lookup?table=collections&value=%s',
            {
                title: title
            }
        );

        jQuery('input[@name=IsbnIssn]').autocomplete
        (
            'Lookup?table=isbnissn&value=%s',
            {
                title: title
            }
        );
        jQuery('textarea[@name=Rev]').autocomplete
        (
            'Lookup?table=revues&value=%s',
            {
                title: title
            }
        );

        jQuery('textarea[@name=Theme]').autocomplete
        (
            'Lookup?table=themes&value=%s',
            {
                title: title
            }
        );

        ThesoLookupUrl='../ThesaurusModule/ThesoLookup';
        jQuery('textarea[@name=MotCle]').autocomplete
        (
            ThesoLookupUrl + '?fre=[%s] or [%s*]',
            {
                asValue: false,
                height: 300,
                onload: ThesoLookup,
                title: title
            }
        );

        jQuery('textarea[@name=Nomp]').autocomplete
        (
            'Lookup?table=nomspropres&value=%s',
            {
                title: title
            }
        );

        jQuery('textarea[@name=CanDes]').autocomplete
        (
            'Lookup?table=candes&value=%s',
            {
                title: title
            }
        );
        
        jQuery('textarea[@name=Loc]').autocomplete
        (
            'Lookup?table=localisations&value=%s',
            {
                title: title
            }
        );
        
        jQuery('input[@name=ProdFich]').autocomplete
        (
            'Lookup?table=producteurs&value=%s',
            {
                title: title
            }
        );
    }
);


/************************************************************
 Gestion de l'aide
 ************************************************************/

function Help(Ctx)
{
	var f=parent.frames["Help"] ;
	if (f && f.location) f.location.replace ("Help.asp#" + Ctx);
	window.status=Ctx;
}

function InitFields()
{
	// affiche l'intro de l'aide une fois la page chargée
	Help("INTRO");

	// affecte un gestionnaire onfocus à tous les éléments du formulaire
	var i, e ;
	var c=document.forms[0].elements ;

	for (i=0; i<c.length;i++)
	{
		e=c[i];
		if (e.name != "")
			e.onfocus=new Function("Help('" + e.name.toUpperCase() +"')") ;
	}
}

/************************************************************
 Contrôles de saisie
 ************************************************************/
function CheckLienAnnexe(Value)
{
    var Annexe=ctrlGetFieldValue(document.forms[0].Annexe)

	// Si des titres d'annexes ont été saisis, les liens vers les annexes sont obligatoires    
    if (Annexe)
	{
		if (Value.length == 0)
        {
			ctrlAlert("Les liens vers les annexes doivent être obligatoirement renseignés si des titres d'annexes ont été indiqués.\n");
			return false;
        }
	}
    // Et inversement, si des liens sont saisis, les titres d'annexes correspondants doivent être indiqués
    else
    {
        if (Value)
        {
            ctrlAlert("Des liens vers les versions électroniques d'annexes ont été saisis mais aucun titre d'annexe n'a été indiqué.\n");
            return false;
        }
    }
    
    // Il doit y avoir autant de titres d'annexes que de liens
    var tLienAnne=Value.split(" ; ");
    var tAnnexe=Annexe.split(Controls[0][2]);

    if (tLienAnne.length != tAnnexe.length)
    {
        h  = "Il doit y avoir autant de titres d'annexes que de liens vers les versions électroniques des annexes.\n\n"
        h += "Les différents liens doivent être séparés les uns des autres par le séparateur ' ; ' (espace, point-virgule, espace)."
        ctrlAlert(h);
        return false;
    }
    
	return true;
}

function CheckForm(ControlsName)
{
	var h ;
	var Statut;

    if ( ctrlFields(ControlsName) ) return true ;
	Statut=document.forms[0].Statut;

	if ( ctrlGetFieldValue(Statut)=="valide" )
	{
        h  = "Votre fiche contient une ou plusieurs erreurs. "
        h += "Elle sera visible du grand public.\n"
        h += "Nous vous conseillons de corriger votre fiche avant de l'enregistrer.\n\n"
        h += "Cliquez sur OK pour enregistrer la fiche, sur Annuler pour la corriger."
        return confirm( h ) ;
	}
    else if ( ctrlGetFieldValue(Statut)=="" )
    {        
        h  = "Impossible d'enregistrer la fiche : vous devez au minimum définir son statut.\n"
        ctrlFormAlert( h ) ;
        return false;
    }
	else
	{
		h  = "Votre fiche contient une ou plusieurs erreurs. "
		h += "Nous vous conseillons de corriger votre fiche avant de l'enregistrer.\n\n"
		h += "Néanmoins, comme, pour le moment, elle n'est pas visible du grand public "
        h += "(car non validée par un administrateur), vous pouvez, si vous le souhaitez, "
		h += "l'enregistrer dans son état actuel.\n"
		h += "Cliquez sur OK pour enregistrer la fiche, sur Annuler pour la corriger."
		return confirm( h ) ;
	}
}

// Formulaire de saisie avec tous les champs
var Controls =
[
[0, "FRE", "/", "CheckForm"],
//        0           		1                                              2       3       4        5       6        7                  8
// Nom du champ    , Libellé du champ                                   , Obl  , MinLen, MaxLen , MinArt, MaxArt, "RegExp"       , "UserFunc"
["Type"            , "Type de document"                                 , true ,       ,        ,       ,     1 ,                ,                ],
["NatText"         , "Nature du texte officiel"                         ,      ,       ,        ,       ,     1 ,                ,                ],
["Aut"             , "Auteurs"                                          ,      ,       ,        ,     1 ,       , "reAuteur"     ,                ],
["Tit"             , "Titre du document"                                ,      ,       ,        ,       ,       ,                ,                ],
["Annexe"          , "Titres des annexes"                               ,      ,       ,        ,       ,       ,                ,                ],
["LienAnne"        , "Adresses Internet des annexes"                    ,      ,       ,        ,       ,       ,                ,"CheckLienAnnexe"],
["CongrTit"        , "Intitulé du congrès"                              ,      ,       ,        ,       ,       ,                ,                ],
["CongrNum"        , "Numéro du congrès"                                ,      ,       ,        ,       ,       ,                ,                ],
["CongrDat"        , "Année du congrès"                                 ,      ,       ,        ,       ,     1 , "reYear"       ,                ],
["CongrLie"        , "Ville du congrès"                                 ,      ,       ,        ,       ,       ,                ,                ],
["DipSpe"          , "Spécialité du diplôme"                            ,      ,       ,        ,       ,       ,                ,                ],
["Date"            , "Année du document ou de soutenance"               ,      ,       ,        ,       ,     1 , "reYearDoc"    ,                ],
["DateText"        , "Date du texte officiel"                           ,      ,       ,        ,       ,     1 , "reDateUsual"  ,                ],
["DatePub"         , "Date de publication du texte officiel"            ,      ,       ,        ,       ,     1 , "reDateUsual"  ,                ],
["DateVali"        , "Date de fin de validité du texte officiel"        ,      ,       ,        ,       ,     1 , "reDateUsual"  ,                ],
["Rev"             , "Titre du périodique"                              ,      ,       ,        ,       ,       ,                ,                ],
["Vol"             , "Volume du périodique"                             ,      ,       ,        ,       ,       ,                ,                ],
["Num"             , "Numéro du périodique"                             ,      ,       ,        ,       ,       ,                ,                ],
["NumTexOf"        , "Numéro du texte officiel"                         ,      ,       ,        ,       ,       ,                ,                ],
["ViePerio"        , "Date de vie et de mort du titre de périodique"	,      ,       ,        ,       ,       , "rePeriod"     ,                ],
["EtatCol"         , "Etat de la collection"                            ,      ,       ,        ,       ,       ,                ,                ],
["Page"            , "Nombre de pages"                                  ,      ,       ,        ,       ,       ,                ,                ],
["PdPf"            , "Page de début et page de fin"                     ,      ,       ,        ,       ,       ,                ,                ],
["Notes"           , "Notes"				                            ,      ,       ,        ,       ,       ,                ,                ],
["Col"             , "Collection"                                       ,      ,       ,        ,       ,       ,                ,                ],
["Edit"            , "Editeur(s)"                                       ,      ,       ,        ,     1 ,       ,                ,                ],
["Lieu"            , "Lieu d'édition ou de soutenance"                  ,      ,       ,        ,     1 ,       ,                ,                ],
["Reed"            , "Mention d'édition"                                ,      ,       ,        ,       ,       ,                ,                ],
["IsbnIssn"        , "ISBN, ISSN"                                       ,      ,       ,        ,       ,     1 ,                ,                ],
["Resu"            , "Résumé"                                           ,      ,       ,        ,       ,       ,                ,                ],
["Theme"           , "Thème"                                            ,      ,       ,        ,       ,     1 , "reUpCase"     ,                ],
["MotCle"          , "Descripteurs"                                     ,      ,       ,        ,     1 ,       , "reUpCase"     ,                ],
["Nomp"            , "Noms propres"                                     ,      ,       ,        ,     1 ,       , "reUpCase"     ,                ],
["CanDes"          , "Candidats descripteurs"                           ,      ,       ,        ,     1 ,       , "reUpCase"     ,                ],
["Lien"            , "Lien vers le document"                            ,      ,       ,        ,       ,       , "reUrlIP"      ,                ],
["Loc"             , "Localisation du document"                         ,      ,       ,        ,     1 ,       , "reNomCentre"  ,                ],
["ProdFich"        , "Producteur de la fiche"                           ,      ,       ,        ,     1 ,       , "reNomCentre"  ,                ],
["Statut"          , "Statut de la notice"                              , true ,       ,        ,       ,       ,                ,                ]
]

// Pour document Article
var ControlsArticle =
[
[0, "FRE", "/", "CheckForm"],
//        0           		1                                              2       3       4        5       6        7                  8
// Nom du champ    , Libellé du champ                                   , Obl  , MinLen, MaxLen , MinArt, MaxArt, "RegExp"       , "UserFunc"
["Type"            , "Type de document"                                 , true ,       ,        ,       ,     1 ,                ,                ],
["Aut"             , "Auteurs"                                          , true ,       ,        ,     1 ,       , "reAuteur"     ,                ],
["Tit"             , "Titre de l'article"                               , true ,       ,        ,       ,       ,                ,                ],
["Date"            , "Année du document"                                , true ,       ,        ,       ,     1 , "reYear"       ,                ],
["Rev"             , "Titre du périodique"                              , true ,       ,        ,       ,       ,                ,                ],
["Vol"             , "Volume du périodique"                             ,      ,       ,        ,       ,       ,                ,                ],
["Num"             , "Numéro du périodique"                             , true ,       ,        ,       ,       ,                ,                ],
["PdPf"            , "Page de début et page de fin"                     , true ,       ,        ,       ,       ,                ,                ],
["Notes"           , "Notes bibliographiques"                           ,      ,       ,        ,       ,       ,                ,                ],
["Resu"            , "Résumé"                                           ,      ,       ,        ,       ,       ,                ,                ],
["Theme"           , "Thème"                                            ,      ,       ,        ,       ,     1 , "reUpCase"     ,                ],
["MotCle"          , "Descripteurs"                                     , true ,       ,        ,     1 ,       , "reUpCase"     ,                ],
["Nomp"            , "Noms propres"                                     ,      ,       ,        ,     1 ,       , "reUpCase"     ,                ],
["CanDes"          , "Candidats descripteurs"                           ,      ,       ,        ,     1 ,       , "reUpCase"     ,                ],
["Lien"            , "Lien vers le document"                            ,      ,       ,        ,       ,       , "reUrlIP"      ,                ],
["ProdFich"        , "Producteur de la fiche"                           , true ,       ,        ,     1 ,       , "reNomCentre"  ,                ],
["Statut"          , "Statut de la notice"                              , true ,       ,        ,       ,       ,                ,                ]
]

// Pour document Livre
var ControlsLivre =
[
[0, "FRE", "/", "CheckForm"],
//        0           		1                                              2       3       4        5       6        7                  8
// Nom du champ    , Libellé du champ                                   , Obl  , MinLen, MaxLen , MinArt, MaxArt, "RegExp"       , "UserFunc"
["Type"            , "Type de document"                                 , true ,       ,        ,       ,     1 ,                ,                ],
["Aut"             , "Auteurs"                                          , true ,       ,        ,     1 ,       , "reAuteur"     ,                ],
["Tit"             , "Titre du document"                                , true ,       ,        ,       ,       ,                ,                ],
["CongrTit"        , "Intitulé du congrès"                              ,      ,       ,        ,       ,       ,                ,                ],
["CongrNum"        , "Numéro du congrès"                                ,      ,       ,        ,       ,       ,                ,                ],
["CongrDat"        , "Année du congrès"                                 ,      ,       ,        ,       ,     1 , "reYear"       ,                ],
["CongrLie"        , "Ville du congrès"                                 ,      ,       ,        ,       ,       ,                ,                ],
["Date"            , "Année du document"                                , true ,       ,        ,       ,     1 , "reYearDoc"    ,                ],
["Page"            , "Nombre de pages"                                  , true ,       ,        ,       ,       ,                ,                ],
["Notes"           , "Notes bibliographiques"                           ,      ,       ,        ,       ,       ,                ,                ],
["Col"             , "Collection"                                       ,      ,       ,        ,       ,       ,                ,                ],
["Edit"            , "Editeur"                                          , true ,       ,        ,     1 ,       ,                ,                ],
["Lieu"            , "Lieu d'édition"                                   , true ,       ,        ,     1 ,       ,                ,                ],
["Reed"            , "Mention d'édition"                                ,      ,       ,        ,       ,       ,                ,                ],
["IsbnIssn"        , "ISBN, ISSN"                                       ,      ,       ,        ,       ,       ,                ,                ],
["Resu"            , "Résumé"                                           ,      ,       ,        ,       ,       ,                ,                ],
["Theme"           , "Thème"                                            ,      ,       ,        ,       ,     1 , "reUpCase"     ,                ],
["MotCle"          , "Descripteurs"                                     , true ,       ,        ,     1 ,       , "reUpCase"     ,                ],
["Nomp"            , "Noms propres"                                     ,      ,       ,        ,     1 ,       , "reUpCase"     ,                ],
["CanDes"          , "Candidats descripteurs"                           ,      ,       ,        ,     1 ,       , "reUpCase"     ,                ],
["Lien"            , "Lien vers le document"                            ,      ,       ,        ,       ,       , "reUrlIP"      ,                ],
["Loc"             , "Localisation du document"                         , true ,       ,        ,     1 ,       , "reNomCentre"  ,                ],
["ProdFich"        , "Producteur de la fiche"                           , true ,       ,        ,     1 ,       , "reNomCentre"  ,                ],
["Statut"          , "Statut de la notice"                              , true ,       ,        ,       ,       ,                ,                ]
]

// Pour document Congrès
var ControlsCongres =
[
[0, "FRE", "/", "CheckForm"],
//        0           		1                                              2       3       4        5       6        7                  8
// Nom du champ    , Libellé du champ                                   , Obl  , MinLen, MaxLen , MinArt, MaxArt, "RegExp"       , "UserFunc"
["Type"            , "Type de document"                                 , true ,       ,        ,       ,     1 ,                ,                ],
["Aut"             , "Auteurs"                                          , true ,       ,        ,     1 ,       , "reAuteur"     ,                ],
["Tit"             , "Titre du document"                                , true ,       ,        ,       ,       ,                ,                ],
["CongrTit"        , "Intitulé du congrès"                              , true ,       ,        ,       ,       ,                ,                ],
["CongrNum"        , "Numéro du congrès"                                , true ,       ,        ,       ,       ,                ,                ],
["CongrDat"        , "Année du congrès"                                 , true ,       ,        ,       ,     1 , "reYear"       ,                ],
["CongrLie"        , "Ville du congrès"                                 , true ,       ,        ,       ,       ,                ,                ],
["Date"            , "Année du document"                                , true ,       ,        ,       ,     1 , "reYearDoc"    ,                ],
["Page"            , "Nombre de pages"                                  , true ,       ,        ,       ,       ,                ,                ],
["Notes"           , "Notes bibliographiques"                           ,      ,       ,        ,       ,       ,                ,                ],
["Col"             , "Collection"                                       ,      ,       ,        ,       ,       ,                ,                ],
["Edit"            , "Editeur(s)"                                       , true ,       ,        ,     1 ,       ,                ,                ],
["Lieu"            , "Lieu d'édition"                                   , true ,       ,        ,     1 ,       ,                ,                ],
["Reed"            , "Mention d'édition"                                ,      ,       ,        ,       ,       ,                ,                ],
["IsbnIssn"        , "ISBN, ISSN"                                       ,      ,       ,        ,       ,     1 ,                ,                ],
["Resu"            , "Résumé"                                           ,      ,       ,        ,       ,       ,                ,                ],
["Theme"           , "Thème"                                            ,      ,       ,        ,       ,     1 , "reUpCase"     ,                ],
["MotCle"          , "Descripteurs"                                     , true ,       ,        ,     1 ,       , "reUpCase"     ,                ],
["Nomp"            , "Noms propres"                                     ,      ,       ,        ,     1 ,       , "reUpCase"     ,                ],
["CanDes"          , "Candidats descripteurs"                           ,      ,       ,        ,     1 ,       , "reUpCase"     ,                ],
["Lien"            , "Lien vers le document"                            ,      ,       ,        ,       ,       , "reUrlIP"      ,                ],
["Loc"             , "Localisation du document"                         , true ,       ,        ,     1 ,       , "reNomCentre"  ,                ],
["ProdFich"        , "Producteur de la fiche"                           , true ,       ,        ,     1 ,       , "reNomCentre"  ,                ],
["Statut"          , "Statut de la notice"                              , true ,       ,        ,       ,       ,                ,                ]
]

// Pour document Rapport
var ControlsRapport =
[
[0, "FRE", "/", "CheckForm"],
//        0           		1                                              2       3       4        5       6        7                  8
// Nom du champ    , Libellé du champ                                   , Obl  , MinLen, MaxLen , MinArt, MaxArt, "RegExp"       , "UserFunc"
["Type"            , "Type de document"                                 , true ,       ,        ,       ,     1 ,                ,                ],
["Aut"             , "Auteurs"                                          , true ,       ,        ,     1 ,       , "reAuteur"     ,                ],
["Tit"             , "Titre"                                            , true ,       ,        ,       ,       ,                ,                ],
["Date"            , "Année du document"                                , true ,       ,        ,       ,     1 , "reYearDoc"    ,                ],
["Rev"             , "Titre du périodique"                              ,      ,       ,        ,       ,       ,                ,                ],
["Vol"             , "Volume du périodique"                             ,      ,       ,        ,       ,       ,                ,                ],
["Num"             , "Numéro du périodique"                             ,      ,       ,        ,       ,       ,                ,                ],
["Page"            , "Nombre de pages"                                  , true ,       ,        ,       ,       ,                ,                ],
["Notes"           , "Notes bibliographiques"                           ,      ,       ,        ,       ,       ,                ,                ],
["Col"             , "Collection"                                       ,      ,       ,        ,       ,       ,                ,                ],
["Edit"            , "Editeur"                                          ,      ,       ,        ,     1 ,       ,                ,                ],
["Lieu"            , "Lieu d'édition"                                   ,      ,       ,        ,     1 ,       ,                ,                ],
["Reed"            , "Mention d'édition"                                ,      ,       ,        ,       ,       ,                ,                ],
["IsbnIssn"        , "ISBN, ISSN"                                       ,      ,       ,        ,       ,     1 ,                ,                ],
["Resu"            , "Résumé"                                           ,      ,       ,        ,       ,       ,                ,                ],
["Theme"           , "Thème"                                            , true ,       ,        ,       ,     1 , "reUpCase"     ,                ],
["MotCle"          , "Descripteurs"                                     , true ,       ,        ,     1 ,       , "reUpCase"     ,                ],
["Nomp"            , "Noms propres"                                     ,      ,       ,        ,     1 ,       , "reUpCase"     ,                ],
["CanDes"          , "Candidats descripteurs"                           ,      ,       ,        ,     1 ,       , "reUpCase"     ,                ],
["Lien"            , "Lien vers le document"                            , true ,       ,        ,       ,       , "reUrlIP"      ,                ],
["ProdFich"        , "Producteur de la fiche"                           , true ,       ,        ,     1 ,       , "reNomCentre"  ,                ],
["Statut"          , "Statut de la notice"                              , true ,       ,        ,       ,       ,                ,                ]
]

// Pour documents Mémoire et Thèse
var ControlsMemoire =
[
[0, "FRE", "/", "CheckForm"],
//        0           		1                                              2       3       4        5       6        7                  8
// Nom du champ    , Libellé du champ                                   , Obl  , MinLen, MaxLen , MinArt, MaxArt, "RegExp"       , "UserFunc"
["Type"            , "Type de document"                                 , true ,       ,        ,       ,     1 ,                ,                ],
["Aut"             , "Auteurs"                                          , true ,       ,        ,     1 ,       , "reAuteur"     ,                ],
["Tit"             , "Titre"                                            , true ,       ,        ,       ,       ,                ,                ],
["DipSpe"          , "Spécialité du diplôme"                            , true ,       ,        ,       ,       ,                ,                ],
["Date"            , "Année de soutenance"                              , true ,       ,        ,       ,     1 , "reYearDoc"    ,                ],
["Page"            , "Nombre de pages"                                  , true ,       ,        ,       ,       ,                ,                ],
["Notes"           , "Notes bibliographiques"                           ,      ,       ,        ,       ,       ,                ,                ],
["Edit"            , "Institution morale de rattachement"               , true ,       ,        ,     1 ,       ,                ,                ],
["Lieu"            , "Lieu d'édition ou de soutenance"                  , true ,       ,        ,     1 ,       ,                ,                ],
["Resu"            , "Résumé"                                           ,      ,       ,        ,       ,       ,                ,                ],
["Theme"           , "Thème"                                            ,      ,       ,        ,       ,     1 , "reUpCase"     ,                ],
["MotCle"          , "Descripteurs"                                     , true ,       ,        ,     1 ,       , "reUpCase"     ,                ],
["Nomp"            , "Noms propres"                                     ,      ,       ,        ,     1 ,       , "reUpCase"     ,                ],
["CanDes"          , "Candidats descripteurs"                           ,      ,       ,        ,     1 ,       , "reUpCase"     ,                ],
["Lien"            , "Lien vers le document"                            ,      ,       ,        ,       ,       , "reUrlIP"      ,                ],
["Loc"             , "Localisation du document"                         , true ,       ,        ,     1 ,       , "reNomCentre"  ,                ],
["ProdFich"        , "Producteur de la fiche"                           , true ,       ,        ,     1 ,       , "reNomCentre"  ,                ],
["Statut"          , "Statut de la notice"                              , true ,       ,        ,       ,       ,                ,                ]
]

// Pour document Texte officiel
var ControlsTexteOfficiel =
[
[0, "FRE", "/", "CheckForm"],
//        0           		1                                              2       3       4        5       6        7                  8
// Nom du champ    , Libellé du champ                                   , Obl  , MinLen, MaxLen , MinArt, MaxArt, "RegExp"       , "UserFunc"
["Type"            , "Type de document"                                 , true ,       ,        ,       ,     1 ,                ,                ],
["NatText"         , "Nature du texte officiel"                         , true ,       ,        ,       ,     1 ,                ,                ],
["Tit"             , "Titre"                                            , true ,       ,        ,       ,       ,                ,                ],
["Annexe"          , "Titres des annexes"                               ,      ,       ,        ,       ,       ,                ,                ],
["LienAnne"        , "Adresses Internet des annexes"                    ,      ,       ,        ,       ,       ,                ,"CheckLienAnnexe"],
["DateText"        , "Date du texte officiel"                           ,      ,       ,        ,       ,     1 , "reDateUsual"  ,                ],
["DatePub"         , "Date de publication du texte officiel"            ,      ,       ,        ,       ,     1 , "reDateUsual"  ,                ],
["DateVali"        , "Date de fin de validité du texte officiel"        ,      ,       ,        ,       ,     1 , "reDateUsual"  ,                ],
["Rev"             , "Titre du périodique"                              , true ,       ,        ,       ,       ,                ,                ],
["Num"             , "Numéro du périodique"                             , true ,       ,        ,       ,       ,                ,                ],
["NumTexOf"        , "Numéro du texte officiel"                         ,      ,       ,        ,       ,       ,                ,                ],
["Resu"            , "Résumé"                                           ,      ,       ,        ,       ,       ,                ,                ],
["Theme"           , "Thème"                                            , true ,       ,        ,       ,     1 , "reUpCase"     ,                ],
["MotCle"          , "Descripteurs"                                     , true ,       ,        ,     1 ,       , "reUpCase"     ,                ],
["Nomp"            , "Noms propres"                                     ,      ,       ,        ,     1 ,       , "reUpCase"     ,                ],
["CanDes"          , "Candidats descripteurs"                           ,      ,       ,        ,     1 ,       , "reUpCase"     ,                ],
["Lien"            , "Lien vers le document"                            , true ,       ,        ,       ,       , "reUrlIP"      ,                ],
["ProdFich"        , "Producteur de la fiche"                           , true ,       ,        ,     1 ,       , "reNomCentre"  ,                ],
["Statut"          , "Statut de la notice"                              , true ,       ,        ,       ,       ,                ,                ]
]

// Pour document Périodique
var ControlsPeriodique =
[
[0, "FRE", "/", "CheckForm"],
//        0           		1                                              2        3       4        5       6        7                 8
// Nom du champ    , Libellé du champ                                   , Obl  , MinLen, MaxLen , MinArt, MaxArt, "RegExp"       , "UserFunc"
["Type"            , "Type de document"                                 , true ,       ,        ,       ,     1 ,                ,                ],
["Rev"             , "Titre du périodique"                              , true ,     2 ,        ,       ,       ,                ,                ],
["ViePerio"        , "Date de vie et de mort"                           , true ,       ,        ,       ,       , "rePeriod"     ,                ],
["EtatCol"         , "Etat de la collection"                            , true ,       ,        ,       ,       ,                ,                ],
["Notes"           , "Notes"                                            ,      ,       ,        ,       ,       ,                ,                ],
["IsbnIssn"        , "ISSN"                                             , true ,       ,        ,       ,       ,                ,                ],
["Lien"            , "Lien vers le document"                            ,      ,       ,        ,       ,       , "reUrlIP"      ,                ],
["ProdFich"        , "Producteur de la fiche"                           , true ,       ,        ,     1 ,       , "reNomCentre"  ,                ],
["Statut"          , "Statut de la notice"                              , true ,       ,        ,       ,       ,                ,                ]
]
