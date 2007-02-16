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
/*
function OpenUrl()
{
	alert("CheckUrl");
	window.open(document.forms[0].Url.value);
}


function CheckThemes(Value)
{
	var t=Value.split(Controls[0][2]);
	if (t.length <= 1) return true;

	var h="," + Value + ",";
	h=h.toUpperCase();

	var Find="Santé publique - généralités"
	Find=Find.toUpperCase();

	if ( h.indexOf(Find) != -1)
	{
		ctrlAlert("Il est interdit de mentionner des thèmes lorsque la valeur '" + Find + "' est sélectionnée.") ;
		return false;
	}
	return true;
}

function CheckActes(Value)
{
	h = Value;
	h2 = ctrlGetFieldValue(document.forms[0].ActesInfo);   
	h3 = ctrlGetFieldValue(document.forms[0].ActesContact);

	if ( h == "1" ) 
	{
	   if ( ( h2 != "" & h3 == "") || ( h3 != "" & h2 == "") )     //si ActesInfo rempli et pas ActesContact ou inversement, OK
	   {
              return true ;
	   }
	   if ( h2 == "" & h3 == "")         //si ni ActesInfo rempli ni ActesContact, alerte
	   {
	      ctrlAlert("Vous devez remplir les champs Précisions et Contact pour les actes.\nCes champs sont obligatoires lorsque la publication d'actes est prévue.") ;
              return false ;
	   }
	}
	return true ;                     //ds les autres cas, OK
}

function CheckDateDebut(Value)
{
	if (document.forms[0].Valid) return true;				// on est en édition de fiche, pas de controle sur la date de début

	var t=Value.split("/")							// Extrait le jour, le mois et l'année
	if (new Date(t[2], t[1]-1, t[0], 23,59,59) >= new Date()) return true;	// Vérifie que la date est supérieure à la date du jour

	ctrlAlert("La date du colloque est erronée. Vous ne pouvez saisir un colloque dont la date est déjà passée !\n");
	return false;
}

function CheckDateFin(Value)
{
	if ( ! CheckDateDebut(Value) ) return false;
	var t=ctrlGetFieldValue(document.forms[0].DateDebut).split("/");	// Date de début
	var t2=Value.split("/")							// Date de fin
	if (new Date(t[2], t[1]-1, t[0]) <= new Date(t2[2], t2[1]-1, t2[0])) return true;	// Vérifie que Debut <= Fin

	ctrlAlert("La date de fin du colloque est erronée. \n Vous ne pouvez saisir un colloque dont la date de fin est antérieure à la date de début !\n");
	return false;
}

function CheckDateValide(Value)
{
	if (Value.length == 0) return true;	// champ vide, pas de contrôles puisqu'il n'est pas obligatoire
	if (document.forms[0].Valid) return true;	// on est en édition de fiche, pas de controle sur la date de début

	var t=Value.split("/")							// Extrait le jour, le mois et l'année
	if (new Date(t[2], t[1]-1, t[0], 23,59,59) >= new Date()) return true;	// Vérifie que la date est supérieure à la date du jour

	ctrlAlert("La date saisie est erronée. Vous ne pouvez saisir une date déjà passée !\n");
	return false;
}

function CheckLienAnnexe(Value)
{
	if (ctrlGetFieldValue(document.forms[0].Annexe))
	{
		if (Value.length == 0)
			ctrlAlert("Le lien vers l'annexe doit être obligatoirement renseigné si le titre de l'annexe a été indiqué.\n");
			return false;
	}
	return true;
}
*/

function CheckForm(ControlsName)
{
	var h ;
	var Valid;

        if ( ctrlFields(ControlsName) ) return true ;
	Valid=document.forms[0].Valid;

	if ( Valid && ctrlGetFieldValue(Valid)=="" )
	{
		h  = "Impossible d'enregistrer la fiche : elle contient des erreurs et la fiche a été validée.\n"
		h += "Pour enregistrer la fiche, corrigez les erreurs qu'elle contient ou rendez la fiche invisible.\n\n"
		h += "Voici le texte de la première erreur :\n\n"+ctrlError
		alert(h) ;
		return false ;
	}
	else
	{
		h  = "Votre fiche contient une ou plusieurs erreurs. Voici le texte de la première :\n\n"
		h += ctrlError
		h += "\n\nNous vous conseillons de corriger votre fiche avant de l'enregistrer.\n"
		h += "Néanmoins, comme elle n'est pas visible pour le moment, vous pouvez, si vous le souhaitez, "
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
["TYPE"            , "Type de document"                                 , true ,       ,        ,       ,     1 ,                ,                ],
["NATTEXT"         , "Nature du texte officiel"                         ,      ,       ,        ,       ,     1 ,                ,                ],
["AUT"             , "Auteurs"                                          ,      ,       ,        ,       ,       ,                ,                ],
["TIT"             , "Titre du document"                                ,      ,       ,        ,       ,       ,                ,                ],
["ANNEXE"          , "Titre de l'annexe"                                ,      ,       ,        ,       ,       ,                ,                ],
["CONGRTIT"        , "Intitulé du congrès"                              ,      ,       ,        ,       ,       ,                ,                ],
["CONGRNUM"        , "Numéro du congrès"                                ,      ,       ,        ,       ,       ,                ,                ],
["CONGRDAT"        , "Année du congrès"                                 ,      ,       ,        ,       ,     1 , "reYear"       ,                ],
["CONGRLIEU"        , "Ville du congrès"                                 ,      ,       ,        ,       ,       ,                ,                ],
["DIPSPE"          , "Spécialité du diplôme"                            ,      ,       ,        ,       ,       ,                ,                ],
["DATE"            , "Année du document ou de soutenance"               ,      ,       ,        ,       ,     1 , "reYearDoc"    ,                ],
["DATETEXT"        , "Date du texte officiel"                           ,      ,       ,        ,       ,     1 , "reDateUsual"  ,                ],
["DATEPUB"         , "Date de publication du texte officiel"            ,      ,       ,        ,       ,     1 , "reDateUsual"  ,                ],
["DATEVALI"        , "Date de fin de validité du texte officiel"        ,      ,       ,        ,       ,     1 , "reDateUsual"  ,                ],
["REV"             , "Titre du périodique"                              ,      ,       ,        ,       ,       ,                ,                ],
["VOL"             , "Volume du périodique"                             ,      ,       ,        ,       ,       ,                ,                ],
["NUM"             , "Numéro du périodique"                             ,      ,       ,        ,       ,       ,                ,                ],
["NUMTEXOF"        , "Numéro du texte officiel"                         ,      ,       ,        ,       ,       ,                ,                ],
["VIEPERIO"        , "Date de vie et de mort du titre de périodique"	,      ,       ,        ,       ,       , "rePeriod"     ,                ],
["ETATCOL"         , "Etat de la collection"                            ,      ,       ,        ,       ,       ,                ,                ],
["PAGE"            , "Nombre de pages"                                  ,      ,       ,        ,       ,       ,                ,                ],
["PDPF"            , "Page de début et page de fin"                     ,      ,       ,        ,       ,       ,                ,                ],
["NOTES"           , "Notes bibliographiques"                           ,      ,       ,        ,       ,       ,                ,                ],
["COL"             , "Collection"                                       ,      ,       ,        ,       ,       ,                ,                ],
["EDIT"            , "Editeur(s)"                                       ,      ,       ,        ,       ,       ,                ,                ],
["LIEU"            , "Lieu d'édition ou de soutenance"                  ,      ,       ,        ,       ,       ,                ,                ],
["REED"            , "Mention d'édition"                                ,      ,       ,        ,       ,       ,                ,                ],
["ISBNISSN"        , "ISBN, ISSN"                                       ,      ,     9 ,     13 ,       ,     1 , "reIsbnIssn"   ,                ],
["RESU"            , "Résumé"                                           ,      ,       ,        ,       ,       ,                ,                ],
["THEME"           , "Thème"                                            ,      ,       ,        ,       ,     1 , "reUpCase"     ,                ],
["MOTCLE"          , "Descripteurs"                                     ,      ,       ,        ,       ,       , "reUpCase"     ,                ],
["NOMP"            , "Noms propres"                                     ,      ,       ,        ,       ,       , "reUpCase"     ,                ],
["CANDES"          , "Candidats descripteurs"                           ,      ,       ,        ,       ,       , "reUpCase"     ,                ],
["LIEN"            , "Lien vers le document"                            ,      ,       ,        ,       ,       , "reUrlIP"      ,                ],
["LOC"             , "Localisation du document"                         ,      ,       ,        ,       ,       , "reNomCentre"  ,                ],
["PRODFICH"        , "Producteur de la fiche"                           ,      ,       ,        ,       ,       , "reNomCentre"  ,                ]
]

// Pour document Article
var ControlsArticle =
[
[0, "FRE", "/", "CheckForm"],
//        0           		1                                              2       3       4        5       6        7                  8
// Nom du champ    , Libellé du champ                                   , Obl  , MinLen, MaxLen , MinArt, MaxArt, "RegExp"       , "UserFunc"
["TYPE"            , "Type de document"                                 , true ,       ,        ,       ,     1 ,                ,                ],
["AUT"             , "Auteurs"                                          , true ,       ,        ,       ,       ,                ,                ],
["TIT"             , "Titre de l'article"                               , true ,       ,        ,       ,       ,                ,                ],
["DATE"            , "Année du document"                                , true ,       ,        ,       ,     1 , "reYear"       ,                ],
["REV"             , "Titre du périodique"                              , true ,       ,        ,       ,       ,                ,                ],
["VOL"             , "Volume du périodique"                             ,      ,       ,        ,       ,       ,                ,                ],
["NUM"             , "Numéro du périodique"                             , true ,       ,        ,       ,       ,                ,                ],
["PDPF"            , "Page de début et page de fin"                     , true ,       ,        ,       ,       ,                ,                ],
["NOTES"           , "Notes bibliographiques"                           ,      ,       ,        ,       ,       ,                ,                ],
["RESU"            , "Résumé"                                           ,      ,       ,        ,       ,       ,                ,                ],
["THEME"           , "Thème"                                            , true ,       ,        ,       ,     1 , "reUpCase"     ,                ],
["MOTCLE"          , "Descripteurs"                                     , true ,       ,        ,       ,       , "reUpCase"     ,                ],
["NOMP"            , "Noms propres"                                     ,      ,       ,        ,       ,       , "reUpCase"     ,                ],
["CANDES"          , "Candidats descripteurs"                           ,      ,       ,        ,       ,       , "reUpCase"     ,                ],
["LIEN"            , "Lien vers le document"                            ,      ,       ,        ,       ,       , "reUrlIP"      ,                ],
["PRODFICH"        , "Producteur de la fiche"                           , true ,       ,        ,     1 ,       , "reNomCentre"  ,                ]
]

// Pour document Livre
var ControlsLivre =
[
[0, "FRE", "/", "CheckForm"],
//        0           		1                                              2       3       4        5       6        7                  8
// Nom du champ    , Libellé du champ                                   , Obl  , MinLen, MaxLen , MinArt, MaxArt, "RegExp"       , "UserFunc"
["TYPE"            , "Type de document"                                 , true ,       ,        ,       ,     1 ,                ,                ],
["AUT"             , "Auteurs"                                          , true ,       ,        ,       ,       ,                ,                ],
["TIT"             , "Titre du document"                                , true ,       ,        ,       ,       ,                ,                ],
["CONGRTIT"        , "Intitulé du congrès"                              ,      ,       ,        ,       ,       ,                ,                ],
["CONGRNUM"        , "Numéro du congrès"                                ,      ,       ,        ,       ,       ,                ,                ],
["CONGRDAT"        , "Année du congrès"                                 ,      ,       ,        ,       ,     1 , "reYear"       ,                ],
["CONGRLIE"        , "Ville du congrès"                                 ,      ,       ,        ,       ,       ,                ,                ],
["DATE"            , "Année du document"                                , true ,       ,        ,       ,     1 , "reYearDoc"    ,                ],
["PAGE"            , "Nombre de pages"                                  , true ,       ,        ,       ,       ,                ,                ],
["NOTES"           , "Notes bibliographiques"                           ,      ,       ,        ,       ,       ,                ,                ],
["COL"             , "Collection"                                       ,      ,       ,        ,       ,       ,                ,                ],
["EDIT"            , "Editeur"                                          , true ,       ,        ,       ,       ,                ,                ],
["LIEU"            , "Lieu d'édition"                                   , true ,       ,        ,       ,       ,                ,                ],
["REED"            , "Mention d'édition"                                ,      ,       ,        ,       ,       ,                ,                ],
["ISBNISSN"        , "ISBN, ISSN"                                       , true ,     9 ,     13 ,       ,     1 , "reIsbnIssn"   ,                ],
["RESU"            , "Résumé"                                           ,      ,       ,        ,       ,       ,                ,                ],
["THEME"           , "Thème"                                            , true ,       ,        ,       ,     1 , "reUpCase"     ,                ],
["MOTCLE"          , "Descripteurs"                                     , true ,       ,        ,       ,       , "reUpCase"     ,                ],
["Nomp"            , "Noms propres"                                     ,      ,       ,        ,       ,       , "reUpCase"     ,                ],
["CANDES"          , "Candidats descripteurs"                           ,      ,       ,        ,       ,       , "reUpCase"     ,                ],
["LIEN"            , "Lien vers le document"                            ,      ,       ,        ,       ,       , "reUrlIP"      ,                ],
["LOC"             , "Localisation du document"                         , true ,       ,        ,       ,       , "reNomCentre"  ,                ],
["PRODFICH"        , "Producteur de la fiche"                           , true ,       ,        ,     1 ,       , "reNomCentre"  ,                ]
]

// Pour document Congrès
var ControlsCongres =
[
[0, "FRE", "/", "CheckForm"],
//        0           		1                                              2       3       4        5       6        7                  8
// Nom du champ    , Libellé du champ                                   , Obl  , MinLen, MaxLen , MinArt, MaxArt, "RegExp"       , "UserFunc"
["TYPE"            , "Type de document"                                 , true ,       ,        ,       ,     1 ,                ,                ],
["AUT"             , "Auteurs"                                          , true ,       ,        ,       ,       ,                ,                ],
["TIT"             , "Titre du document"                                , true ,       ,        ,       ,       ,                ,                ],
["CONGRTIT"        , "Intitulé du congrès"                              , true ,       ,        ,       ,       ,                ,                ],
["CONGRNUM"        , "Numéro du congrès"                                , true ,       ,        ,       ,       ,                ,                ],
["CONGRDAT"        , "Année du congrès"                                 , true ,       ,        ,       ,     1 , "reYear"       ,                ],
["CONGRLIEU"        , "Ville du congrès"                                 , true ,       ,        ,       ,       ,                ,                ],
["DATE"            , "Année du document"                                , true ,       ,        ,       ,     1 , "reYearDoc"    ,                ],
["PAGE"            , "Nombre de pages"                                  , true ,       ,        ,       ,       ,                ,                ],
["NOTES"           , "Notes bibliographiques"                           ,      ,       ,        ,       ,       ,                ,                ],
["COL"             , "Collection"                                       ,      ,       ,        ,       ,       ,                ,                ],
["EDIT"            , "Editeur(s)"                                       , true ,       ,        ,       ,       ,                ,                ],
["LIEU"            , "Lieu d'édition"                                   , true ,       ,        ,       ,       ,                ,                ],
["REED"            , "Mention d'édition"                                ,      ,       ,        ,       ,       ,                ,                ],
["ISBNISSN"        , "ISBN, ISSN"                                       , true ,     9 ,     13 ,       ,     1 , "reIsbnIssn"   ,                ],
["RESU"            , "Résumé"                                           ,      ,       ,        ,       ,       ,                ,                ],
["THEME"           , "Thème"                                            , true ,       ,        ,       ,     1 , "reUpCase"     ,                ],
["MOTCLE"          , "Descripteurs"                                     , true ,       ,        ,       ,       , "reUpCase"     ,                ],
["NOMP"            , "Noms propres"                                     ,      ,       ,        ,       ,       , "reUpCase"     ,                ],
["CANDES"          , "Candidats descripteurs"                           ,      ,       ,        ,       ,       , "reUpCase"     ,                ],
["LIEN"            , "Lien vers le document"                            ,      ,       ,        ,       ,       , "reUrlIP"      ,                ],
["LOC"             , "Localisation du document"                         , true ,       ,        ,       ,       , "reNomCentre"  ,                ],
["PRODFICH"        , "Producteur de la fiche"                           , true ,       ,        ,     1 ,       , "reNomCentre"  ,                ]
]

// Pour document Rapport
var ControlsRapport =
[
[0, "FRE", "/", "CheckForm"],
//        0           		1                                              2       3       4        5       6        7                  8
// Nom du champ    , Libellé du champ                                   , Obl  , MinLen, MaxLen , MinArt, MaxArt, "RegExp"       , "UserFunc"
["TYPE"            , "Type de document"                                 , true ,       ,        ,       ,     1 ,                ,                ],
["AUT"             , "Auteurs"                                          , true ,       ,        ,       ,       ,                ,                ],
["TIT"             , "Titre"                                            , true ,       ,        ,       ,       ,                ,                ],
["DATE"            , "Année du document"                                , true ,       ,        ,       ,     1 , "reYearDoc"    ,                ],
["REV"             , "Titre du périodique"                              ,      ,       ,        ,       ,       ,                ,                ],
["VOL"             , "Volume du périodique"                             ,      ,       ,        ,       ,       ,                ,                ],
["NUM"             , "Numéro du périodique"                             ,      ,       ,        ,       ,       ,                ,                ],
["PAGE"            , "Nombre de pages"                                  , true ,       ,        ,       ,       ,                ,                ],
["NOTES"           , "Notes bibliographiques"                           ,      ,       ,        ,       ,       ,                ,                ],
["COL"             , "Collection"                                       ,      ,       ,        ,       ,       ,                ,                ],
["EDIT"            , "Editeur"                                          ,      ,       ,        ,       ,       ,                ,                ],
["LIEU"            , "Lieu d'édition"                                   ,      ,       ,        ,       ,       ,                ,                ],
["REED"            , "Mention d'édition"                                ,      ,       ,        ,       ,       ,                ,                ],
["ISBNISSN"        , "ISBN, ISSN"                                       ,      ,     9 ,     13 ,       ,     1 , "reIsbnIssn"   ,                ],
["RESU"            , "Résumé"                                           ,      ,       ,        ,       ,       ,                ,                ],
["THEME"           , "Thème"                                            , true ,       ,        ,       ,     1 , "reUpCase"     ,                ],
["MOTCLE"          , "Descripteurs"                                     , true ,       ,        ,       ,       , "reUpCase"     ,                ],
["NOMP"            , "Noms propres"                                     ,      ,       ,        ,       ,       , "reUpCase"     ,                ],
["CANDES"          , "Candidats descripteurs"                           ,      ,       ,        ,       ,       , "reUpCase"     ,                ],
["LIEN"            , "Lien vers le document"                            , true ,       ,        ,       ,       , "reUrlIP"      ,                ],
["PRODFICH"        , "Producteur de la fiche"                           , true ,       ,        ,     1 ,       , "reNomCentre"  ,                ]
]

// Pour documents Mémoire et Thèse
var ControlsMemoire =
[
[0, "FRE", "/", "CheckForm"],
//        0           		1                                              2       3       4        5       6        7                  8
// Nom du champ    , Libellé du champ                                   , Obl  , MinLen, MaxLen , MinArt, MaxArt, "RegExp"       , "UserFunc"
["TYPE"            , "Type de document"                                 , true ,       ,        ,       ,     1 ,                ,                ],
["AUT"             , "Auteurs"                                          , true ,       ,        ,       ,       ,                ,                ],
["TIT"             , "Titre"                                            , true ,       ,        ,       ,       ,                ,                ],
["DIPSPE"          , "Spécialité du diplôme"                            , true ,       ,        ,       ,       ,                ,                ],
["DATE"            , "Année de soutenance"                              , true ,       ,        ,       ,     1 , "reYearDoc"    ,                ],
["PAGE"            , "Nombre de pages"                                  , true ,       ,        ,       ,       ,                ,                ],
["NOTES"           , "Notes bibliographiques"                           ,      ,       ,        ,       ,       ,                ,                ],
["EDIT"            , "Institution morale de rattachement"               , true ,       ,        ,       ,       ,                ,                ],
["LIEU"            , "Lieu d'édition ou de soutenance"                  , true ,       ,        ,       ,       ,                ,                ],
["RESU"            , "Résumé"                                           ,      ,       ,        ,       ,       ,                ,                ],
["THEME"           , "Thème"                                            , true ,       ,        ,       ,     1 , "reUpCase"     ,                ],
["MOTCLE"          , "Descripteurs"                                     , true ,       ,        ,       ,       , "reUpCase"     ,                ],
["NOMP"            , "Noms propres"                                     ,      ,       ,        ,       ,       , "reUpCase"     ,                ],
["CANDES"          , "Candidats descripteurs"                           ,      ,       ,        ,       ,       , "reUpCase"     ,                ],
["LIEN"            , "Lien vers le document"                            ,      ,       ,        ,       ,       , "reUrlIP"      ,                ],
["LOC"             , "Localisation du document"                         , true ,       ,        ,       ,       , "reNomCentre"  ,                ],
["PRODFICH"        , "Producteur de la fiche"                           , true ,       ,        ,     1 ,       , "reNomCentre"  ,                ]
]

// Pour document Texte officiel
var ControlsTexteOfficiel =
[
[0, "FRE", "/", "CheckForm"],
//        0           		1                                              2       3       4        5       6        7                  8
// Nom du champ    , Libellé du champ                                   , Obl  , MinLen, MaxLen , MinArt, MaxArt, "RegExp"       , "UserFunc"
["TYPE"            , "Type de document"                                 , true ,       ,        ,       ,     1 ,                ,                ],
["NATTEXT"         , "Nature du texte officiel"                         , true ,       ,        ,       ,     1 ,                ,                ],
["TIT"             , "Titre"                                            , true ,       ,        ,       ,       ,                ,                ],
["ANNEXE"          , "Titre de l'annexe"                                ,      ,       ,        ,       ,       ,                ,                ],
["DATETEXT"        , "Date du texte officiel"                           , true ,       ,        ,       ,       , "reDateUsual"  ,                ],
["DATEPUB"         , "Date de publication du texte officiel"            , true ,       ,        ,       ,       , "reDateUsual"  ,                ],
["DATEVALI"        , "Date de fin de validité du texte officiel"        ,      ,       ,        ,       ,       , "reDateUsual"  ,                ],
["REV"             , "Titre du périodique"                              , true ,       ,        ,       ,       ,                ,                ],
["NUM"             , "Numéro du périodique"                             , true ,       ,        ,       ,       ,                ,                ],
["NUMTEXOF"        , "Numéro du texte officiel"                         ,      ,       ,        ,       ,       ,                ,                ],
["RESU"            , "Résumé"                                           ,      ,       ,        ,       ,       ,                ,                ],
["THEME"           , "Thème"                                            , true ,       ,        ,       ,     1 , "reUpCase"     ,                ],
["MOTCLE"          , "Descripteurs"                                     , true ,       ,        ,       ,       , "reUpCase"     ,                ],
["NOMP"            , "Noms propres"                                     ,      ,       ,        ,       ,       , "reUpCase"     ,                ],
["CANDES"          , "Candidats descripteurs"                           ,      ,       ,        ,       ,       , "reUpCase"     ,                ],
["LIEN"            , "Lien vers le document"                            , true ,       ,        ,       ,       , "reUrlIP"      ,                ],
["PRODFICH"        , "Producteur de la fiche"                           , true ,       ,        ,     1 ,       , "reNomCentre"  ,                ]
]

// Pour document Périodique
var ControlsPeriodique =
[
[0, "FRE", "/", "CheckForm"],
//        0           		1                                              2        3       4        5       6        7                 8
// Nom du champ    , Libellé du champ                                   , Obl  , MinLen, MaxLen , MinArt, MaxArt, "RegExp"       , "UserFunc"
["TYPE"            , "Type de document"                                 , true ,       ,        ,       ,     1 ,                ,                ],
["REV"             , "Titre du périodique"                              , true ,     2 ,        ,       ,       ,                ,                ],
["VIEPERIO"        , "Date de vie et de mort"                           , true ,       ,        ,       ,       , "rePeriod"     ,                ],
["ETATCOL"         , "Etat de la collection"                            , true ,       ,        ,       ,       ,                ,                ],
["NOTES"           , "Notes"                                            ,      ,       ,        ,       ,       ,                ,                ],
["ISBNISSN"        , "ISSN"                                             , true ,       ,        ,       ,     1 , "reIssnExt"    ,                ],
["LIEN"            , "Lien vers le document"                            ,      ,       ,        ,       ,       , "reUrlIP"      ,                ],
["PRODFICH"        , "Producteur de la fiche"                           , true ,       ,        ,     1 ,       , "reNomCentre"  ,                ]
]
