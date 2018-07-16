document.addEventListener("DOMContentLoaded",function () {
    document.getElementById("uploadf").onchange = function () {
        document.getElementById("filesp").value = this.value.replace('C:\\fakepath\\','');
    };
});