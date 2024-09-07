jQuery(document).ready(function($) {
    console.log('BP Group Clone: jQuery ready function executed');
    console.log('Adding clone buttons to group rows');
    $('.row-actions').each(function() {
        var $this = $(this);
        var groupId = $this.closest('tr').attr('id').replace('group-', '');
        console.log('Processing group row with ID:', groupId); // Debugging line
        if ($this.find('.bp-group-clone').length === 0) {
            $this.prepend('<span class="clone"><a href="#" class="bp-group-clone" data-group-id="' + groupId + '">' + (typeof bpGroupCloneL10n !== 'undefined' && bpGroupCloneL10n.cloneText ? bpGroupCloneL10n.cloneText : 'Clone') + '</a> | </span>');
            console.log('Clone button added for group ID:', groupId); // Debugging line
        }
    });

    $('body').on('click', '.bp-group-clone', function(e) {
        e.preventDefault();
        console.log('Clone button clicked');
        var groupId = $(this).data('group-id');
        var groupName = $(this).closest('tr').find('.column-title strong').text();
        console.log('Initial Group Name:', groupName); // Debugging line
        console.log('Group ID:', groupId);
        console.log('Group Name:', groupName);
        
        var groupStatus = $(this).closest('tr').find('.column-status').text().trim();
        var groupType = $(this).closest('tr').find('.column-group-type').text().trim();
        var cloneDialog = $('<div title="' + bpGroupCloneL10n.cloneGroupTitle + '">' +
            '<p>' + bpGroupCloneL10n.groupStatusText + groupStatus + '</p>' +
            '<p>' + bpGroupCloneL10n.groupTypeText + groupType + '</p>' +
            '<p>' + bpGroupCloneL10n.enterNameText + '</p>' +
            '<input type="text" id="new_group_name" value="' + groupName + '">' +
            '<p>' + bpGroupCloneL10n.optionalComponentsText + '</p>' +
            '<label><input type="checkbox" name="clone_components[]" value="members"> ' + bpGroupCloneL10n.membersText + '</label><br>' +
            '<label><input type="checkbox" name="clone_components[]" value="forums"> ' + bpGroupCloneL10n.forumsText + '</label><br>' +
            '<label><input type="checkbox" name="clone_components[]" value="activity"> ' + bpGroupCloneL10n.activityText + '</label><br>' +
            '<label><input type="checkbox" name="clone_components[]" value="media"> ' + bpGroupCloneL10n.mediaText + '</label><br>' +
            '</div>');

        cloneDialog.dialog({
            modal: true,
            buttons: {
                [bpGroupCloneL10n.cloneButtonText]: function() {
                    var newGroupName = cloneDialog.find('input#new_group_name').val();
                    console.log('Retrieved Group Name:', newGroupName); // Debugging line
                    var selectedComponents = cloneDialog.find('input[name="clone_components[]"]:checked').map(function() {
                        return $(this).val();
                    }).get();

                    if (!newGroupName) {
                        alert('Please enter a group name.');
                        return;
                    }
                    console.log('Selected Components:', selectedComponents); // Debugging line

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'bp_group_clone',
                            group_id: groupId,
                            new_group_name: newGroupName,
                            clone_components: selectedComponents,
                            _wpnonce: bpGroupCloneNonce
                        },
                        success: function(response) {
                            console.log('AJAX Response:', response); // Debugging line
                            if (response.success) {
                                alert('Group cloned successfully!');
                                location.reload();
                            } else {
                                alert('Error: ' + response.data);
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            console.error('AJAX Error:', textStatus, errorThrown); // Debugging line
                            console.error('Response Text:', jqXHR.responseText); // Debugging line
                            $.ajax({
                                url: ajaxurl,
                                type: 'POST',
                                data: {
                                    action: 'bp_group_clone_log_error',
                                    error_details: {
                                        textStatus: textStatus,
                                        errorThrown: errorThrown,
                                        responseText: jqXHR.responseText
                                    }
                                }
                            });
                        }
                    });
                    $(this).dialog("close");
                },
                "Cancel": function() {
                    $(this).dialog("close");
                }
            }
        });
    });
});
