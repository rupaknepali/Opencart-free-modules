var minify=function(str){
	str=str.replace(/\/\*(.|\n)*?\*\//g,"");//删除注释
	str=str.replace(/\s*(\{|\}|\[|\]|\(|\)|\:|\;|\,)\s*/g,"$1");//删除小括号、中括号、大括号、冒号、逗号、分号两边的空格
	str=str.replace(/#([\da-fA-F])\1([\da-fA-F])\2([\da-fA-F])\3/g,"#$1$2$3");//颜色值#aabbcc转换为#abc
	str=str.replace(/:[\+\-]?0(rem|em|ec|ex|px|pc|pt|vh|vw|vmin|vmax|%|mm|cm|in)/g,":0");//删除值为0的单位
	str=str.replace(/\n/g,"");//删除换行符
	str=str.replace(/;\}/g,"}");//删除最后一行语句的分号
	str=str.replace(/^\s+|\s+$/g,"");//删除首尾的空白符
	return str;
};
module.exports=minify;