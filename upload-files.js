const dropZone = document.getElementById("box");
const fileInput = document.getElementById("file-input");
const targetInput = document.getElementById("target");
const typeInput = document.getElementById("type");
const urlWrapper = document.getElementById("url-wrapper");
const fileWrapper = document.getElementById("file-wrapper");
const fileName = document.getElementById("file-name");
const fileSize = document.getElementById("file-size");
const progressContainer = document.getElementById("upload-progress");
const progressBar = document.getElementById("upload-progress-bar");

/*dropZone.addEventListener("click", () => {
    fileInput.click();
});*/
fileInput.addEventListener("change", () => {
    if (fileInput.files.length) {
        uploadFile(fileInput.files[0]);
    }
});
dropZone.addEventListener("dragenter", e => {
    e.preventDefault();
    dropZone.classList.add("dragover");
});
dropZone.addEventListener("dragover", e => {
    e.preventDefault();
});
dropZone.addEventListener("dragleave", () => {
    dropZone.classList.remove("dragover");
});
dropZone.addEventListener("drop", e => {

    e.preventDefault();

    dropZone.classList.remove("dragover");

    if (!e.dataTransfer.files.length)
        return;

    uploadFile(e.dataTransfer.files[0]);

});

function uploadFile(file){
    const formData = new FormData();
    formData.append("file", file);
    progressContainer.style.display = "block";
    progressBar.style.width = "0%";
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "php/file-upload.php");
    xhr.upload.addEventListener("progress", e => {
        if(e.lengthComputable){
            const percent = Math.round(
                e.loaded / e.total * 100
            );
            progressBar.style.width = percent + "%";
        }
    });

    xhr.onload = function(){
        if(xhr.status !== 200){
            // get the error message from the server response if available
            let errorMessage = "Upload fehlgeschlagen.";
            if(xhr.responseJSON && xhr.responseJSON.error){
                errorMessage = xhr.responseJSON.error;
            }
            alert(errorMessage);
            return;
        }
        let data;
        try{
            data = JSON.parse(xhr.responseText);
        }catch(e){
            console.error(xhr.responseText);
            alert("Ungültige Serverantwort.");
            return;
        }

        if(!data.success){
            alert(data.message);
            return;
        }

        // Formular umstellen
        document.getElementById("type").value = "file";
        targetInput.value = data.path;
        urlWrapper.style.display = "none";
        fileWrapper.style.display = "block";
        fileName.innerHTML = "📄 " + data.original_filename;
        fileSize.innerHTML = formatFileSize(data.file_size);
        progressBar.style.width = "100%";
    };
    xhr.send(formData);
}

function formatFileSize(bytes){
    if(bytes < 1024)
        return bytes + " Bytes";

    if(bytes < 1024 * 1024)
        return (bytes/1024).toFixed(1) + " KB";

    if(bytes < 1024 * 1024 * 1024)
        return (bytes/1024/1024).toFixed(2) + " MB";

    return (bytes/1024/1024/1024).toFixed(2) + " GB";

}