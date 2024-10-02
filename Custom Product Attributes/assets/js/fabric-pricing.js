jQuery(document).ready(function($) {
    // Set initial values based on the first option selected
    var $widthSelect = $('#fabric-width');
    var $rateDisplay = $('#fabric-rate');
    var $minLengthDisplay = $('#fabric-min-length');
    var $minPriceDisplay = $('#fabric-min-price');
    var $totalPriceDisplay = $('#total-price');

    function updateFabricDetails() {
        var selectedOption = $widthSelect.find('option:selected');
        var rate = parseFloat(selectedOption.data('rate'));
        var minLength = parseFloat(selectedOption.data('min-length'));
        var minPrice = parseFloat(selectedOption.data('min-price'));
        
        // Update the display fields
        $rateDisplay.text(rate + ' ' + fabricPricingData.currency); // Add currency symbol
        $minLengthDisplay.text(minLength + ' yards');
        $minPriceDisplay.text(minPrice + ' ' + fabricPricingData.currency); // Add currency symbol
        
        calculateTotalPrice();
    }

    function calculateTotalPrice() {
        var selectedOption = $widthSelect.find('option:selected');
        var rate = parseFloat(selectedOption.data('rate'));
        var length = parseFloat($('#fabric-length').val());
        
        if (!isNaN(length) && length > 0) {
            var totalPrice = rate * length;
             
        $('.extra-option:checked').each(function() {
            let percentage = parseFloat($(this).data('price-increase'));
            totalPrice += (totalPrice * (percentage / 100));
        });
            $totalPriceDisplay.text(totalPrice.toFixed(2) + ' ' + fabricPricingData.currency); // Add currency symbol
            
        } else {
            $totalPriceDisplay.text('0.00 ' + fabricPricingData.currency); // Add currency symbol
        }
    }

    // On width change, update fabric details
    $widthSelect.change(function() {
        updateFabricDetails();
    });

    // On length input, recalculate total price
    $('#fabric-length').on('input', function() {
        calculateTotalPrice();
    });

    // Initialize on page load
    updateFabricDetails();

    $('.extra-option').on('change', function() {

        calculateTotalPrice();
       
    });
});


