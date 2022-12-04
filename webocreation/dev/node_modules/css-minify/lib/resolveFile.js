var minify=require("./minify.js");
var fs=require("fs");
var path=require("path");
var resolveFile=function(source){
	var	filename=path.basename(source,".css"),
		dest=path.join(process.cwd(),"css-dist",filename+".min.css");
	if(!path.isAbsolute(source)){
		source=path.resolve(process.cwd(),source);
	}
	fs.readFile(source,"utf8",function(err,data){
		if(err){
			console.log(err);
			throw new Error(source+" 读取失败!")
		}
		data=minify(data);
		fs.writeFile(dest,data,function(err){
			if(err){
				console.log(err);
				throw new Error(dest+" 生成失败!")
			}
			console.log(dest+" 生成成功!")
		})
	})
};
module.exports=resolveFile;