function selectAll(value)
{
     var f = document.getElementById("answers");
     var nb = f.length ;
     if (f[0]==null) alert("Desole, mais votre navigateur ne supporte pas cette fonction...") ;
     for (var i=0;i<nb;i++)
     {
		var e = f[i] ;
		if (e==null) break ;
		if (e.name == "art") e.checked = value ;
     }
};
