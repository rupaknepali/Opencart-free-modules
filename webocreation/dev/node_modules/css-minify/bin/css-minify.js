#!/usr/bin/env node
var path=require("path"),
	fs=require("fs"),
	minify=require("../lib/minify.js"),
	dirExistsSync=require("../lib/dirExistsSync.js")
	resolveFile=require("../lib/resolveFile.js");
var argv=require("yargs")
		.usage("Usage: css-minify <options> [value]")
		.alias("v","version")
		.describe("v","show version info")
		.boolean("v")
		.alias("f","file")
		.describe("f","specify a css source file")
		.alias("d","dir")
		.describe("d","specify a css source directory")
		.help("h")
		.alias("h","help")
		.describe("h","show help info")
		.argv;
var version=require("../package.json").version;
if(argv.v){
	console.log("css-minify "+version);
	return;
}
var file=argv.f,
	dir=argv.d,
	cssReg=/\.css$/;
if(file){
	resolveFile(path.join(process.cwd(),file));
	return;
}
if(dir){
	var dirpath=path.join(process.cwd(),"css-dist");
	if(!dirExistsSync(dirpath)){
		fs.mkdirSync(dirpath);
	}
	fs.readdir(path.join(process.cwd(),dir),function(err,filenames){
		if(err){
			console.log(err);
			throw new Error("读取目录"+path.join(process.cwd(),dir)+"失败")
		}
		filenames.forEach(function(filename){
			if(cssReg.test(filename)){
				resolveFile(path.join(process.cwd(),dir,filename));
			}
		})
		
	})
	return;
}

