/* returns all attributes of a node */
$.fn.getAllAttributes = function() {var attributes = {};
if( this.length ) {$.each( this[0].attributes, function( index, attr ) {attributes[ attr.name ] = attr.value;
} );
}return attributes;
};


/* returns values from a FORM node */
$.fn.asobject=function(){var c={};
var b=this.serializeArray();
$.each(b,function(){if(c[this.name]!==undefined){if(!c[this.name].push){c[this.name]=[c[this.name]]}c[this.name].push(this.value||"")}else{c[this.name]=this.value||""}});
return c};

