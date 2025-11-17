/* ========== Cost Calculator JS */
jQuery(document).ready(function($) {	
    // Get pricing data from WooCommerce variations
    function getVariationPricingData() {
        const form = $('form.variations_form');
        const variations = form.data('product_variations');
        const pricingData = {};
        
        if (variations) {
            variations.forEach(variation => {
                const wallType = variation.attributes['attribute_wall-type'];
                if (wallType) {
                    // Get regular price for fullBlocks
                    const fullBlocksPrice = parseFloat(variation.display_regular_price) || 0;
            		const fullBlockVolume = parseFloat(variation.full_block_volume_m3) || 0;
					
                    // Get cnrBlocks price from custom field
                    const cnrBlocksPrice = parseFloat(variation.cnr_blocks_price) || (fullBlocksPrice * 1.2);
					const cnrBlockVolume = parseFloat(variation.corner_block_volume_m3) || 0;
					
                    pricingData[wallType] = {
                        fullBlockPrice: fullBlocksPrice,
						fullBlockVolume: fullBlockVolume,
                        cnrBlockPrice: cnrBlocksPrice,
						cnrBlockVolume: cnrBlockVolume,
                    };
                }
            });
        }
        return pricingData;
    }
	
    const cChannelPrice = +calculatorPricing.cChannelPrice;
    const cChannelUnitVolume = formatVolume(calculatorPricing.cChannelVolume);
    const bracesPrice = +calculatorPricing.bracesPrice;
    const blockWaste = +calculatorPricing.blockWaste;
    
    let eePricing = getVariationPricingData();
    
    function ceiling(num, significance) {
        return Math.ceil(num / significance) * significance;
    }
    
    // Number formatting functions
    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
    
    function formatCurrency(num) {
        return '$' + num.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
	function formatVolume(num){
		return parseFloat(num).toFixed(6);
	}
    
    // Calculate opening area from dimensions
    function calculateOpeningArea() {
        const openingLength = parseFloat($('#eeOpeningLength').val()) || 0;
        const openingHeight = parseFloat($('#eeOpeningHeight').val()) || 0;
        const openingArea = openingLength * openingHeight;
        $('.opening-area-result').text(formatNumber(openingArea.toFixed(2)) + ' m²');
        return openingArea;
    }
    
    function updatePricingData() {
        eePricing = getVariationPricingData();
        calculateEESystem();
    }
    
    function calculateEESystem() {
        const wallType = $('#wall-type').val();
		const wallLabel = $('#wall_label').val() ? $('#wall_label').val().trim() : null;
        const length = parseFloat($('#eeLength').val()) || 0;
        const height = parseFloat($('#eeHeight').val()) || 0;
        const corners = parseFloat($('#eeCorners').val()) || 0;
        
        // Get opening area from dimensions if toggle is on
        let opening = 0;
        if ($('#eeOpeningToggle').is(':checked')) {
            opening = calculateOpeningArea();
        }
        
        // Check if we have pricing data for the selected wall type
        if (!eePricing[wallType]) {
            // If no pricing data, disable calculator and show message
            $('#addToCart').prop('disabled', true);
            $('#displayTotal').text('Select Wall Type');
            return;
        }
        
        // Calculate block and corner areas
        // const height = parseFloat($('#eeHeight').val()) || 0;
        // let newHeight = Math.round(height);
        // console.log(newHeight, 'newHeight');

        let processedValue;
        if (/^(\d+|[a-zA-Z]+)\.1$/.test(height)) {
            processedValue = height.toString().split('.')[0];
        } else {
            processedValue = height;
        }
        let newHeight = parseFloat(processedValue) || 0;

        const totalAreaM2 = (ceiling(length, 1.2) * ceiling(newHeight, 0.3)) - 
                       (ceiling(newHeight, 0.6) * 1 * corners) - 
                       (opening * 0.9);
        
        // Update area summary
        $('#totalAreaM2').text(formatNumber(totalAreaM2.toFixed(2)));
		const cnrM2 = ceiling(newHeight, 0.6) * 1.2 * corners;
        // $('#m2Rate').text(formatNumber(m2Rate.toFixed(2)));
		
        // Calculate quantities with waste
        const fullBlocks = Math.ceil((totalAreaM2 / 1.2 / 0.6) * (1 + blockWaste));
        const cnrBlocks = Math.ceil((cnrM2 / 1.2 / 0.6) * (1 + blockWaste));
        const cChannel = length;
        const braces = Math.round(length);
        
        // Update quantities with thousand separators
        $('#eeFullBlocks').text(formatNumber(fullBlocks));
        $('#eeCnrBlocks').text(formatNumber(cnrBlocks));
        $('#eeCChannel').text(formatNumber(cChannel.toFixed(0)));
        $('#eeBraces').text(formatNumber(braces));
        
        // Get prices from variation data
        const fullBlocksPrice = eePricing[wallType].fullBlockPrice;
        const fullBlockUnitVolume = formatVolume(eePricing[wallType].fullBlockVolume);
        const cnrBlocksPrice = eePricing[wallType].cnrBlockPrice;
        const cnrBlockUnitVolume = formatVolume(eePricing[wallType].cnrBlockVolume);
		
        $('#eeFullBlocksPrice').text(formatCurrency(fullBlocksPrice));
        $('#eeFullBlocksUnitVolume').text(fullBlockUnitVolume);
		
        $('#eeCnrBlocksPrice').text(formatCurrency(cnrBlocksPrice));
        $('#eeCnrBlocksUnitVolume').text(cnrBlockUnitVolume); 
		
        $('#eeCChannelPrice').text(formatCurrency(cChannelPrice));
        $('#eeCChannelUnitVolume').text(cChannelUnitVolume); 
		
        $('#eeBracesPrice').text(formatCurrency(bracesPrice));
        
        // Calculate totals
        const fullBlocksTotal = fullBlocks * fullBlocksPrice;
        const fullBlocksTotalVolume = parseFloat((fullBlocks * fullBlockUnitVolume).toFixed(4));
		
        const cnrBlocksTotal = cnrBlocks * cnrBlocksPrice;
        const cnrBlocksTotalVolume = parseFloat((cnrBlocks * cnrBlockUnitVolume).toFixed(4));
		
        const cChannelTotal = cChannel * cChannelPrice;
        const cChannelTotalVolume = parseFloat((cChannel * cChannelUnitVolume).toFixed(4));
        const bracesTotal = braces * bracesPrice;

        // console.log(fullBlocksTotalVolume, 'fullBlocksTotalVolume');
        // console.log(cnrBlocksTotalVolume, 'cnrBlocksTotalVolume');
        // console.log(cChannelTotalVolume, 'cChannelTotalVolume');
        
        $('#eeFullBlocksTotal').text(formatCurrency(fullBlocksTotal));
        $('#eeFullBlocksTotalVolume').text(fullBlocksTotalVolume);
		
        $('#eeCnrBlocksTotal').text(formatCurrency(cnrBlocksTotal));
        $('#eeCnrBlocksTotalVolume').text(cnrBlocksTotalVolume);
		
        $('#eeCChannelTotal').text(formatCurrency(cChannelTotal));
        $('#eeCChannelTotalVolume').text(cChannelTotalVolume);
		
        $('#eeBracesTotal').text(formatCurrency(bracesTotal));
        
        // Calculate final totals
        const subtotal = fullBlocksTotal + cnrBlocksTotal + cChannelTotal;
		const totalVolumeM3 = (fullBlocksTotalVolume + cnrBlocksTotalVolume + cChannelTotalVolume).toFixed(4);
        console.log(totalVolumeM3, 'totalVolumeM3');
        $('#eeSubtotal').text(formatCurrency(subtotal));
        $('#eeTotalVolumeM3').text(formatVolume(totalVolumeM3));
        $('#displayTotal').text(formatCurrency(subtotal));
		
		// Calculate and set m2Rate
		const m2Rate = subtotal / totalAreaM2;
        $('#m2Rate').text('$' + formatNumber(m2Rate.toFixed(2)));
        
        // Update hidden fields for WooCommerce (without formatting)
        $('#calculated_price').val(subtotal);
		const calculatedDataObje = {
            area: (length * height).toFixed(2),
            length: length,
            height: height,
            corners: corners,
            wallType: wallType,
            fullBlocks: fullBlocks,
            cnrBlocks: cnrBlocks,
			cChannel: cChannel,
            m2Rate: m2Rate.toFixed(2),
			totalAreaM2: parseFloat(totalAreaM2).toFixed(2) + 'm<sup>2</sup>',
            openingArea: opening,
            totalPrice: subtotal,
			rawSummaryDataIcf: {
				area: (length * height).toFixed(2),
				length: length,
				height: height,
				corners: corners,
				wallType: wallType,
				fullBlocks: fullBlocks,
				cnrBlocks: cnrBlocks,
				cChannel: cChannel,
				totalAreaM2: parseFloat(totalAreaM2).toFixed(2),
				m2Rate: m2Rate.toFixed(2),
				openingArea: opening,
				totalPrice: subtotal,
				volume: formatVolume(totalVolumeM3),
			}
        };
		if(wallLabel){
			calculatedDataObje.wallLabel = wallLabel;
		}
		let calculated_data = JSON.stringify(calculatedDataObje);
        $('#calculated_data').val(calculated_data);
		
        // Enable add to cart button if price is valid
        if (subtotal > 0) {
            $('#addToCart').prop('disabled', false);
        } else {
            $('#addToCart').prop('disabled', true);
        }
        
        return subtotal;
    }
    
    // Initialize pricing data when page loads
    setTimeout(updatePricingData, 100);
    
    // Update pricing data when variation changes
    $('form.variations_form').on('found_variation', function(event, variation) {
        updatePricingData();
    });
    
    // Toggle opening dimensions
    $('#eeOpeningToggle').on('change', function() {
        if ($(this).is(':checked')) {
            $('.opening-dimensions').slideDown(300);
        } else {
            $('.opening-dimensions').slideUp(300);
            // Reset opening dimensions
            $('#eeOpeningLength').val('');
            $('#eeOpeningHeight').val('');
            $('.opening-area-result').text('0.00 m²');
        }
        calculateEESystem();
    });
    
    // Event handlers for input changes
    $('#wall-type, #eeLength, #eeHeight, #eeCorners, #eeOpeningLength, #eeOpeningHeight, #wall_label').on('input change', function() {
        calculateEESystem();
    });
    $(document).on('change', '#wall-type', function(){
		let $this = $(this),
			wallType = $this.val(),
			calculator = $('.woocommerce-cost-calculator');
		if(wallType){
			calculator.slideDown();
		}else{
			calculator.slideUp();
		}
	});
	
	// Add to cart handler - PROPER VERSION
	$('#addToCart').on('click', function(e) {
		e.preventDefault();

		// Store debug info in localStorage before redirect
		localStorage.setItem('calculator_debug', JSON.stringify({
			timestamp: new Date().toISOString(),
			buttonDisabled: $(this).prop('disabled'),
			calculatedPrice: $('#calculated_price').val(),
			calculatedPriceFloat: parseFloat($('#calculated_price').val()),
			formData: $('form.cart').serialize()
		}));

		if ($(this).prop('disabled')) {
			alert('Please enter valid dimensions to calculate price');
			return;
		}

		const calculatedPrice = parseFloat($('#calculated_price').val());

		if (calculatedPrice > 0) {
			// Submit the WooCommerce form
			$('form.cart').submit();
		} else {
			alert('Please calculate a valid price first');
		}
	});

	// Check debug info on page load
	jQuery(document).ready(function($) {
		const debugInfo = localStorage.getItem('calculator_debug');
		if (debugInfo) {
			localStorage.removeItem('calculator_debug'); // Clean up
		}
	});
	calculateEESystem();
});
