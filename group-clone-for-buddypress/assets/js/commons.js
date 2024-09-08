// BuddyPress Group Clone - Common JavaScript Functions

jQuery(document).ready(function($) {
    // Configuration object for easy adjustments
    var config = {
        animationSpeed: 300,
        defaultComponents: bpGroupCloneL10n.selectedFields || [],
        errorDisplayDuration: 3000
    };

    // Function to add clone buttons to group rows
    function addCloneButtons() {
        //console.log('BP Group Clone: Adding clone buttons to group rows');
        $('.row-actions').each(function() {
            var $this = $(this);
            var groupId = $this.closest('tr').attr('id').replace('group-', '');
            if ($this.find('.bp-group-clone').length === 0) {
                $this.prepend('<span class="clone"><a href="#" class="bp-group-clone" data-group-id="' + groupId + '">' + 
                    (bpGroupCloneL10n.cloneText || 'Clone') + '</a> | </span>');
                //console.log('Clone button added for group ID:', groupId);
            }
        });
    }

    // Function to handle clone button click
    function handleCloneButtonClick() {
        $('body').on('click', '.bp-group-clone', function(e) {
            e.preventDefault();
            var $button = $(this);
            var groupId = $button.data('group-id');
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'bp_group_clone_get_details',
                    group_id: groupId,
                    _wpnonce: bpGroupCloneNonce
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        var groupName = response.data.name;
                        var groupStatus = response.data.status;
                        var groupType = response.data.type;

                        //console.log('Clone button clicked for group:', groupId, groupName, groupStatus, groupType);

                        showCloneDialog(groupId, groupName, groupStatus, groupType);
                    } else {
                        displayErrorMessage(response.data || bpGroupCloneL10n.genericErrorMessage);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('AJAX Error:', textStatus, errorThrown);
                    console.error('Response Text:', jqXHR.responseText);
                    console.log('AJAX error function triggered');
                    logAjaxError(jqXHR, textStatus, errorThrown);
                    displayErrorMessage(bpGroupCloneL10n.ajaxErrorMessage);
                }
            });
        });
    }

    // Function to display the clone dialog
    function showCloneDialog(groupId, groupName, groupStatus, groupType) {
        console.log("Groupe Type:".groupType);
        var cloneDialog = $('<div>', {
            title: bpGroupCloneL10n.cloneGroupTitle,
            'aria-label': bpGroupCloneL10n.cloneGroupAriaLabel
        }).append(
            $('<p>').text(bpGroupCloneL10n.groupStatusText + groupStatus),
            $('<p>').text(bpGroupCloneL10n.groupTypeText + (groupType || bpGroupCloneL10n.noGroupTypeText)),
            $('<p>').text(bpGroupCloneL10n.enterNameText),
            $('<input>', {
                type: 'text',
                id: 'new_group_name',
                value: groupName,
                'aria-label': bpGroupCloneL10n.newGroupNameAriaLabel
            }),
            $('<p>').text(bpGroupCloneL10n.enterDescriptionText),
            $('<textarea>', {
                id: 'new_group_description',
                'aria-label': bpGroupCloneL10n.newGroupDescriptionAriaLabel
            }),
            $('<p>').text(bpGroupCloneL10n.optionalComponentsText),
            createComponentCheckbox('members'),
            createComponentCheckbox('forums'),
            createComponentCheckbox('activity'),
            createComponentCheckbox('media')
        );

        cloneDialog.dialog({
            modal: true,
            width: 400,
            show: { effect: 'fadeIn', duration: config.animationSpeed },
            hide: { effect: 'fadeOut', duration: config.animationSpeed },
            buttons: [
                {
                    text: bpGroupCloneL10n.cloneButtonText,
                    click: function() {
                        processClone(groupId, $(this));
                    }
                },
                {
                    text: bpGroupCloneL10n.cancelButtonText,
                    click: function() {
                        $(this).dialog("close");
                    }
                }
            ]
        });
    }

    // Helper function to create component checkboxes
    function createComponentCheckbox(component) {
        return $('<label>').append(
            $('<input>', {
                type: 'checkbox',
                name: 'clone_components[]',
                value: component,
                checked: config.defaultComponents.includes(component)
            }),
            ' ' + bpGroupCloneL10n[component + 'Text']
        ).add('<br>');
    }

    function displayGroupType(groupTypes) {
        if (Array.isArray(groupTypes) && groupTypes.length > 0) {
            console.log('Group Types:', groupTypes);
            return groupTypes.join(', ');

        }
        return bpGroupCloneL10n.noGroupTypeText;
    }

    // Function to process the clone operation
    function processClone(groupId, dialog) {
        var newGroupName = dialog.find('#new_group_name').val();
        var newGroupDescription = dialog.find('#new_group_description').val();
        var selectedComponents = dialog.find('input[name="clone_components[]"]:checked').map(function() {
            return $(this).val();
        }).get();

        if (!newGroupName) {
            displayErrorMessage(bpGroupCloneL10n.emptyGroupNameError);
            return;
        }

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'bp_group_clone',
                group_id: groupId,
                new_group_name: newGroupName,
                new_group_description: newGroupDescription,
                clone_components: selectedComponents,
                _wpnonce: bpGroupCloneNonce
            },
            success: function(response) {
                if (response.success) {
                    displaySuccessMessage(bpGroupCloneL10n.cloneSuccessMessage);
                    setTimeout(function() {
                        location.reload();
                    }, config.errorDisplayDuration);
                } else {
                    displayErrorMessage(response.data || bpGroupCloneL10n.genericErrorMessage);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX Error:', textStatus, errorThrown);
                logAjaxError(jqXHR, textStatus, errorThrown);
                displayErrorMessage(bpGroupCloneL10n.ajaxErrorMessage);
            },
            complete: function() {
                dialog.dialog("close");
            }
        });
    }

    // Function to display error messages
    function displayErrorMessage(message) {
        displayMessage(message, 'error');
    }

    // Function to display success messages
    function displaySuccessMessage(message) {
        displayMessage(message, 'success');
    }

    // Generic function to display messages
    function displayMessage(message, type) {
        $('<div>')
            .addClass('bp-group-clone-message')
            .addClass(type)
            .text(message)
            .appendTo('body')
            .fadeIn(config.animationSpeed)
            .delay(config.errorDisplayDuration)
            .fadeOut(config.animationSpeed, function() { $(this).remove(); });
    }

    // Function to log AJAX errors
    function logAjaxError(jqXHR, textStatus, errorThrown) {
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


    // Initialize the functionality
    addCloneButtons();
    handleCloneButtonClick();
});
