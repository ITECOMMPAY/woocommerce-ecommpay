jQuery(document).ready(function () {

    let show_description = document.getElementById('show_description');
    let pp_mode = document.getElementById('pp_mode');
    let toggle = jQuery('.ecp-toggle-switcher');

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