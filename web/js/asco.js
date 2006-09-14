function selectAll(value)
{
     var f = ElementById("answers");
     var nb = f.length ;
     if (f[0]==null) alert("Désoleé mais votre navigateur ne supporte pas cette fonction...") ;
     for (var i=0;i<nb;i++)
     {
		var e = f[i] ;
		if (e==null) break ;
		if (e.name == "art") e.checked = value ;
     }
};

function ElementById(id)
{
	if (document.getElementById(id)) return document.getElementById(id);
	if (document.all[id]) return document.all[id];
	if (document.layers && document.layers[id]) return document.layers[id];
	return null;
}