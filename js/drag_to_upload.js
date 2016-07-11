

var tgt = arguments[0];
var e = document.createElement("input");
e.setAttribute("id", "upload");
e.type = "file";
e.addEventListener("change", function(event) {
    var dataTransfer = {
        dropEffect: "",
        effectAllowed: "all",
        files: e.files,
        items: {},

        types: [],
        setData: function(format, data) {},
        getData: function(format) {}
    };
    var emit = function(event, target) {
        var evt = document.createEvent("Event");
        evt.initEvent(event, true, false);
        evt.dataTransfer = dataTransfer;
        target.dispatchEvent(evt);
    };
    emit("dragenter", tgt);
    emit("dragover", tgt);
    emit("drop", tgt);
    document.body.removeChild(e);
}, false);
document.body.appendChild(e);
return e;