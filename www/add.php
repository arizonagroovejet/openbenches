<?php
session_start();
require_once ("config.php");
require_once ("mysql.php");
require_once ("functions.php");

//	Start the normal page
include("header.php");
?>
</hgroup>
<?php
[$user_provider, $user_providerID, $user_name] = get_user_details();

if(null == $user_provider) {
	$login_html = "<a href='/login/'>Sign in</a> - or be <strong>anonymous</strong>.";
	} else {
	$login_html = "You are logged in as \"{$user_name}\" from " . ucfirst($user_provider);
}

	echo "<p>{$login_html}</p>";
?>
<?php
	echo $error_message;
?>
	<form id="fileform" action="/upload.php" enctype="multipart/form-data" method="post" onsubmit="true;">
		<h3>Add A Bench</h3>
		<p>Select a photo of the bench's inscription and we'll try to auto-detect the text.<br>
		You can edit the text and add more photos before saving.<br>
		The photo <em>must</em> have GPS information included.<br></p>

		<div id="photo1" class="photo-group" style="display: block;">
			<fieldset>
				<legend>Geotagged Photo</legend>
				<input id="photoFile1" name="userfile1" type="file" accept="image/jpeg" />
				<div id="photoPreview1" style="display: none;"></div>
				<label for="media_type1">This photo is a:</label>
				<?php
					echo get_media_types_html("1");
				?>
			</fieldset>
		</div>&nbsp;
		<br>
		<code id="message"></code>
		<div id="inscription-hidden" style="display: none;">
			<label for="inscription">Inscription:</label><br>
			<textarea id="inscription" name="inscription" cols="40" rows="6"></textarea>
		</div>
		<div id="map-hidden" style="clear:both;display: none;">
			<h3>Drag pin if you need to adjust the bench's location</h3>
			<div id='map' class="hand-drawn" ></div>
		</div>
		<div id="latlong-hidden" style="clear:both;display: none;">
			<input type="text"   id="coordinates"                      value="" disabled="true" />
			<input type="hidden" id="newLongitude" name="newLongitude" value=""/>
			<input type="hidden" id="newLatitude"  name="newLatitude"  value=""/>
		</div>&nbsp;
		<div id="photo2" class="photo-group" style="display: none;">
			<fieldset>
				<legend>Optional photo of same bench</legend>
				<input id="photoFile2" name="userfile2" type="file" accept="image/jpeg" />
				<div id="photoPreview2" style="display: none;"></div>
				<label for="media_type2">This photo is a:</label>
				<?php
					echo get_media_types_html("2");
				?>
			</fieldset>
		</div>&nbsp;
		<div id="photo3" class="photo-group" style="display: none;">
			<fieldset>
				<legend>Optional photo of same bench</legend>
				<input id="photoFile3" name="userfile3" type="file" accept="image/jpeg" />
				<div id="photoPreview3" style="display: none;"></div>
				<label for="media_type3">This photo is a:</label>
				<?php
					echo get_media_types_html("3");
				?>
			</fieldset>
		</div>&nbsp;
		<div id="photo4" class="photo-group" style="display: none;">
			<fieldset>
				<legend>Optional photo of same bench</legend>
				<input id="photoFile4" name="userfile4" type="file" accept="image/jpeg" />
				<div id="photoPreview4" style="display: none;"></div>
				<label for="media_type4">This photo is a:</label>
				<?php
					echo get_media_types_html("4");
				?>
			</fieldset>
		</div>
		<br>
		<fieldset id="progressInfo" style="display:none;">
			<legend>Upload progress</legend>
			<progress id="progressBar" value="0" max="100" style="width:300px;"></progress>
			<h3 id="status"></h3>
			<p id="loaded_n_total"></p>
		</fieldset>
	</form>
	<input class="hand-drawn" type="submit" name="submitButton" id="submitButton" value="Share Bench" style="display: none;"/>

		<br>&nbsp;
		<small>By adding a bench, you agree that you own the copyright of the photo and that you are making it freely available under the <a href="https://creativecommons.org/licenses/by-sa/4.0/">Creative Commons Attribution-ShareAlike 4.0 International (CC BY-SA 4.0) license</a>.<br>
		This means other people can use the photo and its data without having to ask permission, but they will have to give <a href="https://creativecommons.org/licenses/by-sa/4.0/legalcode#s3">appropriate credit</a>. Thanks!<br>
		See our <a href="https://www.openbenches.org/blog/privacy/">privacy policy</a> to understand how your photo's data is used.
	</small>
	<script src="/libs/jquery.3.3.1/jquery-3.3.1.min.js"></script>
	<script src="/libs/vision/key.js"></script>
	<script src="/libs/vision/vision.js"></script>
	<script src="/libs/load-image.2.19.0/load-image.all.min.js"></script>
	<script type="text/javascript">
		var previewWidth = 800;
		var map = null;

		$("#photoFile1").onchange = function (e) {
			var preview1 = $("#photoPreview1");
			//	If a photo was added already, remove it.
			while (preview1.hasChildNodes()) {
				preview1.removeChild(preview1.lastChild);
			}
			//	Display the element
			preview1.style.display = "block";
			//	Add a quick canvas to the screen showing the image
			var loadingImage = loadImage(
				e.target.files[0],
				function (img) { preview1.appendChild(img); },
				{ maxWidth: previewWidth, canvas: true}
			);
			if (!loadingImage) {}
			$("#photo2").style.display = "block";

			//	Check for GPS data
			var exifdata = loadImage.parseMetaData(
				e.target.files[0],
				function (data) {
					if (!data.imageHead) {
						return;
					}

					if ( typeof data.exif == 'undefined' ) {
						alert("EXIF Warning! No GPS tags detected in photo.\nPlease check your camera's settings or add a different photo.");
						return;
					} else if (data.exif.get("GPSLongitude") == null) {
						alert("Warning! No GPS tags detected in photo.\nPlease check your camera's settings or add a different photo.");
						return;
					}
					// upload the photo for text detection
					uploadFiles(e);	

					var exifLong    = data.exif.get("GPSLongitude");
					var exifLongRef = data.exif.get("GPSLongitudeRef");
					var exifLat     = data.exif.get("GPSLatitude"); 
					var exifLatRef  = data.exif.get("GPSLatitudeRef"); 
					
					//	Correct for negative values
					if (exifLatRef == "S") {
						var latitude = (exifLat[0]*-1) + (( (exifLat[1]*-60) + (exifLat[2]*-1) ) / 3600);
					} else {
						var latitude = exifLat[0] + (( (exifLat[1]*60) + exifLat[2] ) / 3600);
					}

					if (exifLongRef == "W") {
						var longitude = (exifLong[0]*-1) + (( (exifLong[1]*-60) + (exifLong[2]*-1) ) / 3600);											} else {
						var longitude = exifLong[0] + (( (exifLong[1]*60) + exifLong[2] ) / 3600);
					}
					//	Show the map
					$("#map-hidden").show();
					$("#latlong-hidden").show();

					var attribution = 'Map data © <a href="https://openstreetmap.org">OpenStreetMap</a> contributors, ' +
						'<a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, ' +
						'Imagery © <a href="https://mapbox.com">Mapbox</a>';

					var grayscale = L.tileLayer('https://api.mapbox.com/styles/v1/mapbox/outdoors-v10/tiles/256/{z}/{x}/{y}?access_token=pk.eyJ1IjoiZWRlbnQiLCJhIjoiY2o0dmVnZjVhMHA1MDMzcWp4YmtzcWNsbiJ9.DIgG0nrOK4bnswj2RFfLgQ', {
						minZoom: 2,
						maxZoom: 18,
						attribution: attribution,
						id: 'mapbox.light'
					});

					var satellite = L.tileLayer('https://api.mapbox.com/styles/v1/mapbox/satellite-streets-v10/tiles/256/{z}/{x}/{y}?access_token=pk.eyJ1IjoiZWRlbnQiLCJhIjoiY2o0dmVnZjVhMHA1MDMzcWp4YmtzcWNsbiJ9.DIgG0nrOK4bnswj2RFfLgQ', {
							minZoom: 2,
							maxZoom: 18,
							attribution: attribution,
							id: 'mapbox.satellite'
						});

					if (map != null){
						map.remove();
					}
					map = L.map('map');
					// Centre the map
					map.setView([latitude, longitude], 18);
					var baseMaps = {
						"Map View": grayscale,
						"Satellite View": satellite
					};

					grayscale.addTo(map);

					L.control.layers(baseMaps).addTo(map);

					var marker = L.marker([latitude, longitude], { draggable: true }).addTo(map);

					var coordinates  = $("#coordinates");
					var newLatitude  = $("#newLatitude");
					var newLongitude = $("#newLongitude");
					coordinates.value  = latitude.toPrecision(7) + ',' + longitude.toPrecision(7);
					newLongitude.value = longitude;
					newLatitude.value  = latitude;
					marker.on('dragend', function(event){
						newLat =  event.target._latlng.lat.toPrecision(7);
						newLong = event.target._latlng.lng.toPrecision(7);
						coordinates.value  = newLat + ',' + newLong;
						newLongitude.value = newLong;
						newLatitude.value  =  newLat;
					});
				}
			);
		};
		$("#photoFile2").onchange = function (e) {
			var preview2 = $("#photoPreview2");
			//	If a photo was added already, remove it.
			while (preview2.hasChildNodes()) {
				preview2.removeChild(preview2.lastChild);
			}
			//	Display the element
			preview2.style.display = "block";
			//	Add a quick canvas to the screen showing the image
			var loadingImage = loadImage(
				e.target.files[0],
				function (img) { preview2.appendChild(img); },
				{ maxWidth: previewWidth, canvas: true}
			);
			if (!loadingImage) {}
			//	Show the next upload box
			$("#photo3").style.display = "block";
		}
		$("#photoFile3").onchange = function (e) {
			var preview3 = $("#photoPreview3");
			//	If a photo was added already, remove it.
			while (preview3.hasChildNodes()) {
				preview3.removeChild(preview3.lastChild);
			}
			//	Display the element
			preview3.style.display = "block";
			//	Add a quick canvas to the screen showing the image
			var loadingImage = loadImage(
				e.target.files[0],
				function (img) { preview3.appendChild(img); },
				{ maxWidth: previewWidth, canvas: true}
			);
			if (!loadingImage) {}
			//	Show the next upload box
			$("#photo4").style.display = "block";
		}
		$("#photoFile4").onchange = function (e) {
			var preview4 = $("#photoPreview4");
			//	If a photo was added already, remove it.
			while (preview4.hasChildNodes()) {
				preview4.removeChild(preview4.lastChild);
			}
			//	Display the element
			preview4.style.display = "block";
			//	Add a quick canvas to the screen showing the image
			var loadingImage = loadImage(
				e.target.files[0],
				function (img) { preview4.appendChild(img); },
				{ maxWidth: previewWidth, canvas: true}
			);
			if (!loadingImage) {}
		}
		//	Disable button once clicked & let user know that the media are being uploaded
		$("#submitButton").on('click', function() {
			$("#submitButton").prop( "disabled", true );
			$("#submitButton").prop( "value", "Uploading!" );
			uploadFile();
		});
		
		function uploadFile() {
			$("#progressInfo").style.display = "block";

			var formdata = new FormData();
			formdata.append("userfile1",    $("#photoFile1").files[0]);
			formdata.append("userfile2",    $("#photoFile2").files[0]);
			formdata.append("userfile3",    $("#photoFile3").files[0]);
			formdata.append("userfile4",    $("#photoFile4").files[0]);
			formdata.append("inscription",  $("#inscription").value);
			formdata.append("newLongitude", $("#newLongitude").value);
			formdata.append("newLatitude",  $("#newLatitude").value);
			
			var ajax = new XMLHttpRequest();
			ajax.upload.addEventListener("progress", progressHandler);
			ajax.addEventListener("load",  completeHandler);
			ajax.addEventListener("error", errorHandler);
			ajax.addEventListener("abort", abortHandler);
			ajax.open("POST", "/upload.php");
			ajax.send(formdata);
		}

		function progressHandler(event) {
			$("#loaded_n_total").innerHTML = "Uploaded " + event.loaded.toLocaleString() + " bytes of " + event.total.toLocaleString();
			var percent = (event.loaded / event.total) * 100;
			$("#progressBar").value = Math.round(percent);
			$("#status").innerHTML  = Math.round(percent) + "% uploaded... please wait";
		}

		function completeHandler(event) {
			var reply = event.target.responseText;
			if (isNaN(reply)) {
				$("#status").innerHTML = reply;
			} else {
				$("#status").innerHTML = "Upload complete. Redirecting you.";
				window.location.replace("/bench/"+reply);
			}
		}

		function errorHandler(event) {
			$("#status").innerHTML = "Upload Failed";
		}

		function abortHandler(event) {
			$("#status").innerHTML = "Upload Aborted";
		}
	</script>
<?php
	include("footer.php");
