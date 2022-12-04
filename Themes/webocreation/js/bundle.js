var testRequire=require("./test");window.onload=function(){testRequire(),alert("This is test")};
var Test=function(){console.log("require is working!")};module.exports=function(){return Test()};