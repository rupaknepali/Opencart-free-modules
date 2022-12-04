var Test = function () {
  console.log('require is working!');
}
module.exports = function() {
    return Test();
};
