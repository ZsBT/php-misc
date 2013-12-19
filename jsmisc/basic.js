
/*	form elements' values as JSON object */

HTMLFormElement.prototype.asobject=function(){
  var i=0,ret={};
  while(el=this[i++])
    if(el&&el.name)
      ret[el.name]=el.value||"";
  return ret;
};
