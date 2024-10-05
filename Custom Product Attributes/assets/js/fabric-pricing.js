jQuery(document).ready(function ($) {
    // Set initial values based on the first option selected

    $('form.cart').on('submit', function(e) {
        var fabricWidth = $('select[name="fabric_width"]').val();  // Get the fabric width
        var fabricLength = $('input[name="fabric_length"]').val();  // Get the fabric length
        var fabricweight = $('select[name="fabric_weight"]').val(); 

        if (!fabricWidth || !fabricLength || !fabricweight) {
            e.preventDefault(); // Stop form submission
            alert('Please select a fabric width and enter a valid fabric length.'); // Show error message
            return false;
        }
    });

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
        var minprice = parseFloat(selectedOption.data('min-price'));

        if (!isNaN(length) && length > 0) {
            var totalPrice = rate * length;
            console.log(totalPrice + " tot " + " min " + minprice)
            if(totalPrice < minprice){
                totalPrice = minprice;
            }
            var optionprice = 0;
            $('.extra-option:checked').each(function () {
                let percentage = parseFloat($(this).data('price-increase'));
                optionprice += (totalPrice * (percentage / 100));
            });
            totalPrice = totalPrice + optionprice;
            $totalPriceDisplay.text(totalPrice.toFixed(2) + ' ' + fabricPricingData.currency); // Add currency symbol

        } else {
            $totalPriceDisplay.text('0.00 ' + fabricPricingData.currency); // Add currency symbol
        }
    }

    // On width change, update fabric details
    $widthSelect.change(function () {
        updateFabricDetails();
    });

    // On length input, recalculate total price
    $('#fabric-length').on('input', function () {
        var selectedOption = $widthSelect.find('option:selected');
        var minlength = parseFloat(selectedOption.data('min-length'));
        var selectedlength = parseFloat($(this).val())
        if(selectedlength < minlength){
            alert("Please select a minimum of " + minlength + " yards");
            return;
        }
        calculateTotalPrice();
    });

    // Initialize on page load
    updateFabricDetails();

    $('.extra-option').on('change', function () {

        calculateTotalPrice();

    });

    loadFabricWeights();

    function loadFabricWeights() {
        var fabricArray = fabricPricingDataarray.fabricdata;

        var filterednewarray = [];
        if (fabricArray.length > 0) {
            filterednewarray.push(fabricArray[0].weight);
        }

        fabricArray.forEach(element => {
            //    console.log(element.weight)
            if (!filterednewarray.includes(element.weight)) {
                filterednewarray.push(element.weight);
            }
        });
        var fabricweighthtml = `<label>Fabric Weight</label><select name="fabric_weight" id="fabricweightselect"><option>--Select--</option>`;
        filterednewarray.forEach(fabricweight => {
            fabricweighthtml += `<option value="${fabricweight}">${fabricweight}</option>`
        })
        fabricweighthtml += `</select>`

        $("#fabricweight").html(fabricweighthtml);

        $("#fabricweightselect").on('change', function () {
            $("#fabric-width").html();
            var selectedWieght = $(this).val();
            console.log(selectedWieght + " hey")
            var widths = fabricArray.filter(fabric => fabric.weight === selectedWieght);
            // console.log(widths);
            var fabricwidthhtml = '';
            if(widths){
                widths.forEach(fabricwidth=>{
                    fabricwidthhtml += `<option
                    data-rate="${fabricwidth.rate}"
                    data-min-length="${fabricwidth.min_length}"
                    data-min-price="${fabricwidth.min_price}"
                    value="${fabricwidth.width}">${fabricwidth.width}</option>`
                })

                $("#fabric-width").html(fabricwidthhtml);
            }
        })


    }



});



