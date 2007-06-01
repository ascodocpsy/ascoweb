function selectAll(value)
{
     var f = ElementById("answers");
     if (f==null) return ;

     var nb = f.length ;

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
	if (document.all && document.all[id]) return document.all[id];
	if (document.layers && document.layers[id]) return document.layers[id];
	return null;
}