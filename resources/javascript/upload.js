
// ************************ Drag and drop ***************** //
let dropArea = document.getElementById("droparea");
/* lastTarget is set first on dragenter, then
   compared with during dragleave. */
var lastTarget = null;

window.addEventListener('dragenter', function(e)
{
	e.preventDefault();
	lastTarget = e.target; // cache the last target here
	// unhide our dropzone overlay
	document.getElementById('droparea').style.visibility = 'visible';
	document.getElementById('droparea').style.opacity = 1;
});

window.addEventListener('dragover', function(e)
{
	e.preventDefault();
});

window.addEventListener('dragleave', function(e)
{
	// this is the magic part. when leaving the window,
	// e.target happens to be exactly what we want: what we cached
	// at the start, the dropzone we dragged into.
	// so..if dragleave target matches our cache, we hide the dropzone.
	// `e.target === document` is a workaround for Firefox 57
	e.preventDefault();
	if(e.target === lastTarget || e.target === document)
	{
		document.getElementById('droparea').style.visibility = 'hidden';
		document.getElementById('droparea').style.opacity = 0;
	}
});

// Handle dropped files
window.addEventListener('drop', handleDrop, false);
dropArea.addEventListener('drop', handleDrop, false);

function handleDrop(e) {
  console.log("handle drop fired " + e.target);
  e.preventDefault();
  e.stopPropagation();
  var dt = e.dataTransfer;
  var files = dt.files;

  handleFiles(files);
}

let uploadProgress = [];
let progressBar = document.getElementById('progress-bar');

function initializeProgress(numFiles) {
  progressBar.value = 0;
  uploadProgress = [];

  for(let i = numFiles; i > 0; i--) {
	uploadProgress.push(0);
  }
}

function updateProgress(fileNumber, percent) {
  uploadProgress[fileNumber] = percent;
  let total = uploadProgress.reduce((tot, curr) => tot + curr, 0) / uploadProgress.length;
  console.debug('update', fileNumber, percent, total);
  progressBar.value = total;
}

function handleFiles(files) {
  files = [...files];
  initializeProgress(files.length);
  files.forEach(uploadFile);
  files.forEach(previewFile);
}

function previewFile(file) {
  let reader = new FileReader();
  reader.readAsDataURL(file);
  reader.onloadend = function() {
	let img = document.createElement('img');
	img.src = reader.result;
	img.style.height = "20vh";
	document.getElementById('gallery').appendChild(img);
  }
}

function uploadFile(file, i) {
	console.log(file);
	console.log(i);
  var url = 'https://planwatch.org/scripts/userfiles.php';
  var xhr = new XMLHttpRequest();
  var formData = new FormData();
  var user = document.getElementById('user').value;

  const regex = /[\s\(\)\'\"]/gi;
  var cleanedFilename=file.name.replace(regex,'_');
  console.log(cleanedFilename);
  var imageURL = "/userfiles/view/" + user + "/" + cleanedFilename;


  xhr.open('POST', url, true);
  xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');


  // Update progress (can be used to show progress indicator)
  xhr.upload.addEventListener("progress", function(e) {
	updateProgress(i, (e.loaded * 100.0 / e.total) || 100);
  })

  xhr.addEventListener('readystatechange', function(e) {
	if (xhr.readyState == 4 && xhr.status >= 200 && xhr.status < 300) {
	  console.log(xhr.responseText);
	  updateProgress(i, 100); // <- Add this
	  document.getElementById('responseArea').innerHTML += cleanedFilename + "<br>";

		// var sizedImageURL = "/image//width/600/" + cleanedFilename;
		insertTag('textbox','<img style=\"max-width: 100%;\" src=\"//planwatch.org/' + xhr.responseText + '\"',' />');


		document.getElementById('droparea').style.visibility = 'hidden';
		document.getElementById('droparea').style.opacity = 0;
	}
	else if (xhr.readyState == 4 && xhr.status > 300) {
	  // Error. Inform the user
	  document.getElementById('responseArea').innerHTML += cleanedFilename + " <strong>FAILED " + xhr.status + "</strong><br>";
	  console.log(xhr.responseText);
	}
  })

  formData.append('action', 'upload');
  formData.append('user', document.getElementById('user').value);
  formData.append('file', file);
  formData.append('upload_filename', cleanedFilename);

  xhr.send(formData);
}