var fs=require("fs");
function dirExistsSync(path) {
    try{
        var stat=fs.statSync(path);
        return stat.isDirectory();
    }catch(e){
        return false;
    }
}
module.exports=dirExistsSync;