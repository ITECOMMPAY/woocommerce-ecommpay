jQuery(document).ready(function () {

    let test_mode = document.getElementById('test');
    let show_description = document.getElementById('show_description');
    let pp_mode = document.getElementById('pp_mode');
    let toggle = jQuery('.ecp-toggle-switcher');

    if (test_mode !== null) {
        toggleTest(test_mode.checked);
        test_mode.addEventListener('change', function(e) {
            toggleTest(e.target.checked);
        });
    }

    if (show_description !== null) {
        toggleDesc(show_description.checked);
        show_description.addEventListener('change', function(e) {
            toggleDesc(e.target.checked);
        });
    }

    if (pp_mode !== null) {
        toggleMissClick(pp_mode.value);
        pp_mode.addEventListener('change', function (e) {
            toggleMissClick(e.target.value);
        })
    }

    function toggleTest(checked) {
        if (checked) {
            jQuery('#project_id').parents('tr').hide();
            jQuery('#salt').parents('tr').hide();
        } else {
            jQuery('#salt').parents('tr').show();
            jQuery('#project_id').parents('tr').show();
        }
    }

    function toggleDesc(checked) {
        if (checked) {
            jQuery('#description').parents('tr').show();
        } else {
            jQuery('#description').parents('tr').hide();
        }
    }

    function toggleMissClick(value) {
        if (value === 'popup') {
            jQuery('#pp_close_on_miss_click').parents('tr').show();
        } else {
            jQuery('#pp_close_on_miss_click').parents('tr').hide();
        }

        if (value === 'embedded') {
            jQuery('#show_description').parents('tr').hide();
            jQuery('#description').parents('tr').hide();
        } else {
            jQuery('#show_description').parents('tr').show();
            jQuery('#description').parents('tr').show();
        }
    }

    if (toggle !== 'undefined') {
        toggle.on('click', function() {
            let blockName = this.id + '-toggle';
            let block = document.getElementById(blockName);

            if (block.classList.contains('hidden')) {
                block.classList.remove('hidden');
            } else {
                block.classList.add('hidden');
            }
        });
    }
});