$.fn.biliupload = function(options) {
    var $self = this;

    if ($self.length > 0) {
    	options = (!options) ? {} : options;
    	options.endpoint = ($self.data("upload-endpoint")) ? $self.data("upload-endpoint") : options.endpoint;
    	options.maxfiles = ($self.data("upload-maxfiles")) ? $self.data("upload-maxfiles") : options.maxfiles;
    	options.maxsize = ($self.data("upload-maxsize")) ? $self.data("upload-maxsize") : options.maxsize;
    	options.filesVar = ($self.data("upload-files")) ? $self.data("upload-files") : options.filesVar;

    	var getTotalSizeInQueue = function(up) {
    		var intReturn = 0;
    		
    		for (var i = 0; i < up.files.length; i++) {
    			intReturn += up.files[i].size;
    		}
    		
    		return intReturn;
    	};
    	
    	var addUploadProgress = function(up, fileId) {
    		if ($("#progress-" + fileId).length == 0) {
	    		var template = "<div id=\"progress-" + fileId + "\" class=\"progress-row\" style=\"padding: 10px 0 0 0;\">" +
	    			"<button type=\"button\" class=\"btn btn-link btn-xs pull-right\" style=\"line-height: 1.1;\"><i class=\"fa fa-times\"></i></button>" + 
	    			"<div class=\"progress active progress-striped progress-3x\">" +
	    			"<div class=\"progress-bar progress-bar-success\" role=\"progressbar\" aria-valuenow=\"0\" aria-valuemin=\"0\" aria-valuemax=\"100\" style=\"width:0%;\">" +
	    			"<span class=\"sr-only\"></span>" +
	    			"</div></div></div>";
	    		
	    		$self.closest("span").before(template);
	    		
	    		$self.closest(".vf__multifielditem").find("#progress-" + fileId + " button").on("click", function(){
	    			cancelUpload(up, fileId);
	    		});
    		}
    	}
    	
    	var cancelUpload = function(up, fileId) {
    		//*** Remove the file from the upload queue.
    	    var objFile = up.getFile(fileId);
    	    
    		if (objFile.status == plupload.UPLOADING) {
    			up.stop();
    	    }
    		up.removeFile(objFile);
    		
    		//*** Remove the UI elements.
    		removeUpload(fileId);
    	}
    	
    	var finishUpload = function(file) {
    		var template = "<span class=\"text-truncate col-lg-11\">" + file.name + "</span>";
    		
    		$("#progress-" + file.id + " .progress").remove();
    		$("#progress-" + file.id).append(template);
    		
    		$("#" + options.filesVar).val(file.id);
    		
    		$self.find("#progress-" + file.id + " button").off("click").on("click", function(){
    			removeUpload(file.id);
    		});
    	}
    	
    	var removeUpload = function(fileId) {    		
    		//*** Remove the UI elements.
    		$("#progress-" + fileId).remove();
    		
    		$("#" + options.filesVar).val("");
    		
    		$self.show();
    	}
    	
    	var uploader = new plupload.Uploader({
    		runtimes : 'html5,flash,silverlight',
    		browse_button : $self.attr("id"),
            drop_element : $self.attr("id"),
    		chunk_size: '1mb',
    		url : options.endpoint,
    		flash_swf_url : '/js/Moxie.swf',
    		silverlight_xap_url : '/js/Moxie.xap',
    		filters : {
    		   	max_file_size : options.maxsize,
    		   	mime_types : [
    		   	    {title : "All files", extensions : "*"}
    		   	]
    		},	    	 
	        init: {	     
	            FilesAdded: function(up) {
	        		if (typeof options.maxfiles != "undefined") {
	        			if (options.maxfiles < up.files.length) {
	        				for (var i = up.files.length; i > (options.maxfiles - up.files.length); i--) {
	        			        up.files.pop();
	        			    }
	        			}
	        		}
	        		
	        		if (typeof options.maxsize != "undefined") {
	        			if (options.maxsize < (getTotalSizeInQueue(up))) {
	        				for (var i = up.files.length; i >= 0; i--) {
	        			        up.files.pop();
	        			        
	        			        if (getTotalSizeInQueue(up) < options.maxsize) {
	        			        	break;
	        			        }
	        			    }
	        			}
	        		}
	        		
	        		if (up.files.length > 0) {
	        			$.each(up.files, function(i, file) {
	        				addUploadProgress(up, file.id);
	        			});
	        		}
	        		
	        		$self.hide();
	        	
	        		up.refresh(); // Reposition Flash/Silverlight

	            },	     
	            QueueChanged: function(up) {
	        		if (up.files.length > 0 && (up.state == undefined || up.state != plupload.STARTED)) {
	        			up.start();
	        		}
	            },	     
	        	UploadFile: function(up, file) {
	        		//*** Add the file id to the upload session. This is used by the back-end.
	        		up.settings.multipart_params = {id : file.id};
	        	},
	            UploadProgress: function(up, file) {
	        		$("#progress-" + file.id + " .progress-bar")
	        			.css("width", file.percent + "%")
	        			.attr("aria-valuenow", file.percent);
	            },	     
	            FileUploaded: function(up, file, response) {
	            	finishUpload(file);
	            },	     
	            Error: function(up, err) {
	                document.getElementById('console').innerHTML += "\nError #" + err.code + ": " + err.message;
	            }
	        }
    	});
    	
    	uploader.init();
    }
    
    return this;
};