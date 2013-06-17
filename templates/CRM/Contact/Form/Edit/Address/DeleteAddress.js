// http://civicrm.org/licensing
(function($) {
  var addressBlockData = new Array();
  var addressAttribute = new Array();

  $('document').ready(function() {
    $('div.crm-edit-address-block.boxBlock').each(function(){ 
      var blockId = $(this).attr('id');
      if (blockId) {
	blockId = blockId.split('Block_');
	var element = 'Address_Block_' + blockId;
	if ($('#edit-params-' + blockId)) {
	  $("#" + element).attr('data-edit-params', $('#edit-params-' + blockId).val());
	}
	else {
	  $("#" + element).attr('data-edit-params', 0);
	}
      }
    });
 
    $('#addressBlock')
    // Delete an address
    .on('click', '.delete-address-block', function() {
      var addressBlockId = $(this).attr('blockId'); // address block id
      var $block = $(this).closest('.crm-edit-address-block.boxBlock');
      var aid = $block.data('edit-params').aid; // address id
      var contactId = $block.data('edit-params').cid; // contact id
      if(contactId && aid && aid > 0) { 
        CRM.confirm(function() {
          CRM.api('address', 'delete', {id: aid}, {success:
            function(data) {
	      CRM.alert('', ts('Address Deleted'), 'success');
		
	      var blockCount = 0;
	      var actualBlockCount = 0;

	      // unset block from html
	      removeBlock($block.data('edit-params').block_name, addressBlockId);

	      // count no. of blocks
	      $('div.crm-edit-address-block.boxBlock').each(function(){
		actualBlockCount++;
	      });

	      if (actualBlockCount >= 1) {
		// array used to map old & new address block id
		var blockMapping = new Array();

		$('div.crm-edit-address-block.boxBlock').each(function(){
		  var blockId = $(this).attr('id');
		  if (blockId) {
		    blockCount++;
		    blockId = blockId.split('Block_');
		    // set block id mapping array
		    blockMapping[blockCount] = blockId[1];

		    // copy address block element's data
		    copyBlockElements(contactId, 'Address' , blockId[1], blockCount);

		    //remove hidden elements related to the current address block
		    var name1 = 'address[' + blockId[1] + '][master_id]';
		    var name2 = 'contact_select_id[' + blockId[1] + ']';
		    cj("input[name='" + name1 + "']").remove();
		    cj("input[name='" + name2 + "']").remove();
		   
		    // unset block from html
		    $(this).empty().remove();
		      actualBlockCount--;
		    }
		});

		// build address block(s) & set copied data to newly created blocks
		if (actualBlockCount >= 0 && blockCount >=1) {
		  for (var i=1; i<=blockCount; i++) {
		    // build address block
		    buildAddressBlock('Address', 'CRM_Contact_Form_Contact', i);
		      
		    // set copied data to newly created address block elements
		    setBlockElements(contactId, 'Address',  blockMapping[i], i);
		  }
		}
	      }
	      else if (actualBlockCount == 0 && blockCount == 0) {
		 // clear first block data
		 clearFirstBlock('Address', 1, 1);
	      }

	      // display Add Address button
	      if (blockCount >= 1) {
		var element = 'Address_Block_';
		var lastelement = $( '[id^="'+ element +'"]:last' ).attr('id').slice( element.length );
		if (lastelement) {
		  $('#addMoreAddress' + lastelement).show( );
		}
	      }
            }
          });
        },
        {
          message: ts('Are you sure you want to delete this address?')
        });
      }
      else {
	removeBlock('Address', addressBlockId);  
      }
      return false;
    })
  });
 
 // Function to copy address block element's data
 function copyBlockElements(contactId, blockName , blockId, newBlockId) {
   // element counter to build unique key for array that stores data to be copied later
   var elementCount = 0;
   var element =  blockName + '_Block_' + blockId;

   // copy data-edit-params attribute
   addressBlockData[ contactId + '-' + newBlockId + '-data-edit-params' ] = $("#" + element).attr('data-edit-params');

   // loop through address block to copy element's value to be used later
   $("#" + element +" input, " + "#" + element + " select").each(function () {
     elementCount++;
     var elementId = $(this).attr('id');
     // store element's value in the array
     if ($(this).attr('type') == 'checkbox') {
       addressBlockData[ contactId + '-' + newBlockId + '-' + elementCount ] = $(this).is(":checked");
     }
     else {
       addressBlockData[ contactId + '-' + newBlockId + '-' + elementCount ] = $(this).val();
     }
   });
 }

 // Function to set address block element's data
 function setBlockElements(contactId, blockName, oldBlockId, blockId) {
   // element counter to build unique key to access array that stores data copied earlier
   var elementCount = 0;
   var element =  blockName + '_Block_' + blockId;

   // set data-edit-params attribute
   $("#" + element).attr('data-edit-params', addressBlockData[ contactId + '-' + blockId + '-data-edit-params' ]);
   
   // loop through address block elements and set their value from array built earlier
   $("#" + element +" input, " + "#" + element + " select").each(function () {
     elementCount++;
     // set address block element's value from array built earlier
     $(this).val(addressBlockData[ contactId + '-' + blockId + '-' + elementCount ]);
   });

   // Add hidden element to store data-edit-params attribute
   $('<input>').attr({
     type: 'hidden',
     id: 'edit-params-' + blockId,
     name: 'edit-params' + blockId,
     value: addressBlockData[ contactId + '-' + blockId + '-data-edit-params' ]
   }).appendTo('#' + element);
 }
})(cj);