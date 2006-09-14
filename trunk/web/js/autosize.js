function autosizeInit()
{
    var c = document.getElementsByTagName("textarea");

    for (i=0; i<c.length; i++)
    {
		if ( c[i].className.match(/(^|\s)autoheight(\s|$)/) ) 
		{
		    if (c[i].addEventListener) 
		    	c[i].addEventListener("keyup", autosizeevent, 0);
	    	else
	    		c[i].onkeyup=autosizeevent;
    		autosize(c[i]);
		}    		
    }
}    
window.onload=autosizeInit;

function autosize(target)
{
//    window.setTimeout('autosizereal(e)',1);
  //target.style.height=0+'px'; 
	var maxheight=700;
    var h='';
                
  	oldheight=parseInt(target.style.height);
  	if (isNaN(oldheight)) oldheight=0;
  	newheight=target.scrollHeight;// + target.style.marginTop + target.style.marginBottom;

	if (!target.minheight)
	{
		target.minheight=target.offsetHeight;
		h+='set minheight to ' + newheight + ', ';
	}
	
	h+='minheight=' + target.minheight + ', ';
	if (newheight<target.minheight) newheight=target.minheight;
	if (newheight > maxheight)
	{
		newheight=maxheight;
		if (target.style.overflow!='') target.style.overflow='';
	}
	else
	{
		if (target.style.overflow!='hidden') target.style.overflow='hidden';
	}
  		
  	if (Math.abs(oldheight-newheight)>6)
  	{
		h+='scrollheight=' + target.scrollHeight + ', height=' + target.style.height;
	  	target.style.height=newheight+'px';
	  	h+=' // scrollheight=' + target.scrollHeight + ', height=' + target.style.height;
  	}
  	else
  		h+='inchangé';

	window.status=h;
}

function autosizeevent(e)
{
    var target
    if (!e) var e = window.event
    if (e.target) target = e.target
    else if (e.srcElement) target = e.srcElement
    if (target.nodeType == 3) // defeat Safari bug
       target = target.parentNode

	autosize(target);
	return false;
}
