/************************************************************
 Ascodocpsy
 Load.js
 Ajout/Modification d'une notice
 Gestion de l'aide et contrôles de saisie
 ************************************************************/


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
    //if (ctrlGetFieldValue(document.forms[0].Annexe))    
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
    var tLienAnne=Value.split(";");
    var tAnnexe=Annexe.split(Controls[0][2]);

    if (tLienAnne.length != tAnnexe.length)
    {
        ctrlAlert("Il doit y avoir autant de titres d'annexes que de liens vers les versions électroniques des annexes.\n");
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
		h  = "Impossible d'enregistrer la fiche : elle contient des erreurs et la fiche a été validée par un administrateur. "
        h += "Elle est donc visible du grand public.\n"
		h += "Pour enregistrer la fiche, corrigez les erreurs qu'elle contient.\n\n"
		h += "Voici le texte de la première erreur :\n\n"+ctrlError
		alert(h) ;
		return false ;
	}
    else if ( ctrlGetFieldValue(Statut)=="" )
    {        
        h  = "Impossible d'enregistrer la fiche : elle contient des erreurs.\n"
        h += "Vous n'avez notamment pas défini son statut.\n"
        h += "Pour enregistrer la fiche, corrigez les erreurs qu'elle contient et définissez son statut.\n\n"
        h += "Voici le texte de la première erreur :\n\n"+ctrlError
        alert(h) ;
        return false ;
    }
	else
	{
		h  = "Votre fiche contient une ou plusieurs erreurs. Voici le texte de la première :\n\n"
		h += ctrlError
		h += "\n\nNous vous conseillons de corriger votre fiche avant de l'enregistrer.\n"
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
["Aut"             , "Auteurs"                                          ,      ,       ,        ,       ,       ,                ,                ],
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
["Notes"           , "Notes bibliographiques"                           ,      ,       ,        ,       ,       ,                ,                ],
["Col"             , "Collection"                                       ,      ,       ,        ,       ,       ,                ,                ],
["Edit"            , "Editeur(s)"                                       ,      ,       ,        ,       ,       ,                ,                ],
["Lieu"            , "Lieu d'édition ou de soutenance"                  ,      ,       ,        ,       ,       ,                ,                ],
["Reed"            , "Mention d'édition"                                ,      ,       ,        ,       ,       ,                ,                ],
["IsbnIssn"        , "ISBN, ISSN"                                       ,      ,       ,        ,       ,       ,                ,                ],
["Resu"            , "Résumé"                                           ,      ,       ,        ,       ,       ,                ,                ],
["Theme"           , "Thème"                                            ,      ,       ,        ,       ,     1 , "reUpCase"     ,                ],
["MotCle"          , "Descripteurs"                                     ,      ,       ,        ,       ,       , "reUpCase"     ,                ],
["Nomp"            , "Noms propres"                                     ,      ,       ,        ,       ,       , "reUpCase"     ,                ],
["CanDes"          , "Candidats descripteurs"                           ,      ,       ,        ,       ,       , "reUpCase"     ,                ],
["Lien"            , "Lien vers le document"                            ,      ,       ,        ,       ,       , "reUrlIP"      ,                ],
["Loc"             , "Localisation du document"                         ,      ,       ,        ,       ,       , "reNomCentre"  ,                ],
["ProdFich"        , "Producteur de la fiche"                           ,      ,       ,        ,       ,       , "reNomCentre"  ,                ],
["Statut"          , "Statut de la notice"                              , true ,       ,        ,       ,       ,                ,                ]
]

// Pour document Article
var ControlsArticle =
[
[0, "FRE", "/", "CheckForm"],
//        0           		1                                              2       3       4        5       6        7                  8
// Nom du champ    , Libellé du champ                                   , Obl  , MinLen, MaxLen , MinArt, MaxArt, "RegExp"       , "UserFunc"
["Type"            , "Type de document"                                 , true ,       ,        ,       ,     1 ,                ,                ],
["Aut"             , "Auteurs"                                          , true ,       ,        ,       ,       ,                ,                ],
["Tit"             , "Titre de l'article"                               , true ,       ,        ,       ,       ,                ,                ],
["Date"            , "Année du document"                                , true ,       ,        ,       ,     1 , "reYear"       ,                ],
["Rev"             , "Titre du périodique"                              , true ,       ,        ,       ,       ,                ,                ],
["Vol"             , "Volume du périodique"                             ,      ,       ,        ,       ,       ,                ,                ],
["Num"             , "Numéro du périodique"                             , true ,       ,        ,       ,       ,                ,                ],
["PdPf"            , "Page de début et page de fin"                     , true ,       ,        ,       ,       ,                ,                ],
["Notes"           , "Notes bibliographiques"                           ,      ,       ,        ,       ,       ,                ,                ],
["Resu"            , "Résumé"                                           ,      ,       ,        ,       ,       ,                ,                ],
["Theme"           , "Thème"                                            ,      ,       ,        ,       ,     1 , "reUpCase"     ,                ],
["MotCle"          , "Descripteurs"                                     , true ,       ,        ,       ,       , "reUpCase"     ,                ],
["Nomp"            , "Noms propres"                                     ,      ,       ,        ,       ,       , "reUpCase"     ,                ],
["CanDes"          , "Candidats descripteurs"                           ,      ,       ,        ,       ,       , "reUpCase"     ,                ],
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
["Aut"             , "Auteurs"                                          , true ,       ,        ,       ,       ,                ,                ],
["Tit"             , "Titre du document"                                , true ,       ,        ,       ,       ,                ,                ],
["CongrTit"        , "Intitulé du congrès"                              ,      ,       ,        ,       ,       ,                ,                ],
["CongrNum"        , "Numéro du congrès"                                ,      ,       ,        ,       ,       ,                ,                ],
["CongrDat"        , "Année du congrès"                                 ,      ,       ,        ,       ,     1 , "reYear"       ,                ],
["CongrLie"        , "Ville du congrès"                                 ,      ,       ,        ,       ,       ,                ,                ],
["Date"            , "Année du document"                                , true ,       ,        ,       ,     1 , "reYearDoc"    ,                ],
["Page"            , "Nombre de pages"                                  , true ,       ,        ,       ,       ,                ,                ],
["Notes"           , "Notes bibliographiques"                           ,      ,       ,        ,       ,       ,                ,                ],
["Col"             , "Collection"                                       ,      ,       ,        ,       ,       ,                ,                ],
["Edit"            , "Editeur"                                          , true ,       ,        ,       ,       ,                ,                ],
["Lieu"            , "Lieu d'édition"                                   , true ,       ,        ,       ,       ,                ,                ],
["Reed"            , "Mention d'édition"                                ,      ,       ,        ,       ,       ,                ,                ],
["IsbnIssn"        , "ISBN, ISSN"                                       , true ,       ,        ,       ,       ,                ,                ],
["Resu"            , "Résumé"                                           ,      ,       ,        ,       ,       ,                ,                ],
["Theme"           , "Thème"                                            ,      ,       ,        ,       ,     1 , "reUpCase"     ,                ],
["MotCle"          , "Descripteurs"                                     , true ,       ,        ,       ,       , "reUpCase"     ,                ],
["Nomp"            , "Noms propres"                                     ,      ,       ,        ,       ,       , "reUpCase"     ,                ],
["CanDes"          , "Candidats descripteurs"                           ,      ,       ,        ,       ,       , "reUpCase"     ,                ],
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
["Aut"             , "Auteurs"                                          , true ,       ,        ,       ,       ,                ,                ],
["Tit"             , "Titre du document"                                , true ,       ,        ,       ,       ,                ,                ],
["CongrTit"        , "Intitulé du congrès"                              , true ,       ,        ,       ,       ,                ,                ],
["CongrNum"        , "Numéro du congrès"                                , true ,       ,        ,       ,       ,                ,                ],
["CongrDat"        , "Année du congrès"                                 , true ,       ,        ,       ,     1 , "reYear"       ,                ],
["CongrLie"        , "Ville du congrès"                                 , true ,       ,        ,       ,       ,                ,                ],
["Date"            , "Année du document"                                , true ,       ,        ,       ,     1 , "reYearDoc"    ,                ],
["Page"            , "Nombre de pages"                                  , true ,       ,        ,       ,       ,                ,                ],
["Notes"           , "Notes bibliographiques"                           ,      ,       ,        ,       ,       ,                ,                ],
["Col"             , "Collection"                                       ,      ,       ,        ,       ,       ,                ,                ],
["Edit"            , "Editeur(s)"                                       , true ,       ,        ,       ,       ,                ,                ],
["Lieu"            , "Lieu d'édition"                                   , true ,       ,        ,       ,       ,                ,                ],
["Reed"            , "Mention d'édition"                                ,      ,       ,        ,       ,       ,                ,                ],
["IsbnIssn"        , "ISBN, ISSN"                                       , true ,       ,        ,       ,       ,                ,                ],
["Resu"            , "Résumé"                                           ,      ,       ,        ,       ,       ,                ,                ],
["Theme"           , "Thème"                                            ,      ,       ,        ,       ,     1 , "reUpCase"     ,                ],
["MotCle"          , "Descripteurs"                                     , true ,       ,        ,       ,       , "reUpCase"     ,                ],
["Nomp"            , "Noms propres"                                     ,      ,       ,        ,       ,       , "reUpCase"     ,                ],
["CanDes"          , "Candidats descripteurs"                           ,      ,       ,        ,       ,       , "reUpCase"     ,                ],
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
["Aut"             , "Auteurs"                                          , true ,       ,        ,       ,       ,                ,                ],
["Tit"             , "Titre"                                            , true ,       ,        ,       ,       ,                ,                ],
["Date"            , "Année du document"                                , true ,       ,        ,       ,     1 , "reYearDoc"    ,                ],
["Rev"             , "Titre du périodique"                              ,      ,       ,        ,       ,       ,                ,                ],
["Vol"             , "Volume du périodique"                             ,      ,       ,        ,       ,       ,                ,                ],
["Num"             , "Numéro du périodique"                             ,      ,       ,        ,       ,       ,                ,                ],
["Page"            , "Nombre de pages"                                  , true ,       ,        ,       ,       ,                ,                ],
["Notes"           , "Notes bibliographiques"                           ,      ,       ,        ,       ,       ,                ,                ],
["Col"             , "Collection"                                       ,      ,       ,        ,       ,       ,                ,                ],
["Edit"            , "Editeur"                                          ,      ,       ,        ,       ,       ,                ,                ],
["Lieu"            , "Lieu d'édition"                                   ,      ,       ,        ,       ,       ,                ,                ],
["Reed"            , "Mention d'édition"                                ,      ,       ,        ,       ,       ,                ,                ],
["IsbnIssn"        , "ISBN, ISSN"                                       ,      ,       ,        ,       ,       ,                ,                ],
["Resu"            , "Résumé"                                           ,      ,       ,        ,       ,       ,                ,                ],
["Theme"           , "Thème"                                            ,      ,       ,        ,       ,     1 , "reUpCase"     ,                ],
["MotCle"          , "Descripteurs"                                     , true ,       ,        ,       ,       , "reUpCase"     ,                ],
["Nomp"            , "Noms propres"                                     ,      ,       ,        ,       ,       , "reUpCase"     ,                ],
["CanDes"          , "Candidats descripteurs"                           ,      ,       ,        ,       ,       , "reUpCase"     ,                ],
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
["Aut"             , "Auteurs"                                          , true ,       ,        ,       ,       ,                ,                ],
["Tit"             , "Titre"                                            , true ,       ,        ,       ,       ,                ,                ],
["DipSpe"          , "Spécialité du diplôme"                            , true ,       ,        ,       ,       ,                ,                ],
["Date"            , "Année de soutenance"                              , true ,       ,        ,       ,     1 , "reYearDoc"    ,                ],
["Page"            , "Nombre de pages"                                  , true ,       ,        ,       ,       ,                ,                ],
["Notes"           , "Notes bibliographiques"                           ,      ,       ,        ,       ,       ,                ,                ],
["Edit"            , "Institution morale de rattachement"               , true ,       ,        ,       ,       ,                ,                ],
["Lieu"            , "Lieu d'édition ou de soutenance"                  , true ,       ,        ,       ,       ,                ,                ],
["Resu"            , "Résumé"                                           ,      ,       ,        ,       ,       ,                ,                ],
["Theme"           , "Thème"                                            ,      ,       ,        ,       ,     1 , "reUpCase"     ,                ],
["MotCle"          , "Descripteurs"                                     , true ,       ,        ,       ,       , "reUpCase"     ,                ],
["Nomp"            , "Noms propres"                                     ,      ,       ,        ,       ,       , "reUpCase"     ,                ],
["CanDes"          , "Candidats descripteurs"                           ,      ,       ,        ,       ,       , "reUpCase"     ,                ],
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
["DateText"        , "Date du texte officiel"                           , true ,       ,        ,       ,       , "reDateUsual"  ,                ],
["DatePub"         , "Date de publication du texte officiel"            , true ,       ,        ,       ,       , "reDateUsual"  ,                ],
["DateVali"        , "Date de fin de validité du texte officiel"        ,      ,       ,        ,       ,       , "reDateUsual"  ,                ],
["Rev"             , "Titre du périodique"                              , true ,       ,        ,       ,       ,                ,                ],
["Num"             , "Numéro du périodique"                             , true ,       ,        ,       ,       ,                ,                ],
["NumTexOf"        , "Numéro du texte officiel"                         ,      ,       ,        ,       ,       ,                ,                ],
["Resu"            , "Résumé"                                           ,      ,       ,        ,       ,       ,                ,                ],
["Theme"           , "Thème"                                            ,      ,       ,        ,       ,     1 , "reUpCase"     ,                ],
["MotCle"          , "Descripteurs"                                     , true ,       ,        ,       ,       , "reUpCase"     ,                ],
["Nomp"            , "Noms propres"                                     ,      ,       ,        ,       ,       , "reUpCase"     ,                ],
["CanDes"          , "Candidats descripteurs"                           ,      ,       ,        ,       ,       , "reUpCase"     ,                ],
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
