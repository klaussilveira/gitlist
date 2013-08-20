$(function() {
	
	function error(message){
		$("div.messages").html('<div class="alert alert-error"><a class="close" data-dismiss="alert">×</a>' + message +'</div>');
	}
	
	function success(message){
		$("div.messages").html('<div class="alert alert-success"><a class="close" data-dismiss="alert">×</a>' + message +'</div>');
	}
	
	
	$(".remove-repo").on('click', function() {
		
		var repofilderBase64 = $(this).attr('data-repofolder');
		var $removeButton = $(this);

		$.getJSON('admin/delete/'+ repofilderBase64, function(response) {
			if(response.type == 'success'){
				
				$removeButton.closest('tr').fadeOut(300, 
					function() {
						$removeButton.remove();
						success(response.message);
 
					}
				);			
			} else if (response.type == 'error'){
				error(response.message);
			}
		});
		
		return false;
	});
});
