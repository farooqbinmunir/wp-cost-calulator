/* ========== Reolok Cost Calculator JS */
jQuery(document).ready(function($) {    
    function getReolokVariationPricingData() {
        const form = $('form.variations_form');
        const variations = form.data('product_variations');
        const pricingData = {};
        
        if (variations && variations.length > 0) {
            variations.forEach(variation => {
                const wallType = variation.attributes['attribute_wall-type'];
                
                if (wallType) {
                    const cassettePrice = variation.reolok_cassette_price && variation.reolok_cassette_price !== '' ? 
                        parseFloat(variation.reolok_cassette_price) : null;
                        
                    const cassetteVolume = variation.reolok_cassette_volume && variation.reolok_cassette_volume !== '' ? 
                        parseFloat(variation.reolok_cassette_volume) : null;
                        
                    const joiningTrussPrice = variation.reolok_joining_truss_price && variation.reolok_joining_truss_price !== '' ? 
                        parseFloat(variation.reolok_joining_truss_price) : null;
                        
                    const joiningTrussVolume = variation.reolok_joining_truss_volume && variation.reolok_joining_truss_volume !== '' ? 
                        parseFloat(variation.reolok_joining_truss_volume) : null;
                        
                    const endcapPrice = variation.reolok_endcap_price && variation.reolok_endcap_price !== '' ? 
                        parseFloat(variation.reolok_endcap_price) : null;
                        
                    const endcapVolume = variation.reolok_endcap_volume && variation.reolok_endcap_volume !== '' ? 
                        parseFloat(variation.reolok_endcap_volume) : null;
                    
                    pricingData[wallType] = {
                        cassettePrice: cassettePrice,
                        cassetteVolume: cassetteVolume,
                        joiningTrussPrice: joiningTrussPrice,
                        joiningTrussVolume: joiningTrussVolume,
                        endcapPrice: endcapPrice,
                        endcapVolume: endcapVolume
                    };
                }
            });
        } else {
            pricingData['RL162'] = {
                cassettePrice: 421.23,
                cassetteVolume: 0.5925,
                joiningTrussPrice: 42.11,
                joiningTrussVolume: 0.00648,
                endcapPrice: 75.6,
                endcapVolume: 0.1095
            };
        }
        
        return pricingData;
    }

    const materialWaste = Number(calculatorPricing.reolok_material_waste) || 0.05;
    const fullCassetteThreshold = Number(calculatorPricing.reolok_full_cassette_price_threshold) || (3.2/3.6);
    const bottomTrackPrice = Number(calculatorPricing.reolok_bottom_track_price) || 30.96;
    const bottomTrackVolume = Number(calculatorPricing.reolok_bottom_track_volume) || 0.0008352;
    
    let reolokPricing = getReolokVariationPricingData();

    function ceiling(num, significance) {
        return Math.ceil(num / significance) * significance;
    }

    function formatNumber(num) {
        if (isNaN(num) || num === null) return '0';
        return num.toLocaleString();
    }

    function formatCurrency(num) {
        if (num === null || num === undefined || isNaN(num)) {
            return '$0.00';
        }
        return '$' + parseFloat(num).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
    function formatVolume(volume) {
        if (volume === null || volume === undefined || isNaN(volume)) {
            return '0.00';
        }
        return volume.toFixed(6);
    }

    function updateReolokPricingData() {
        reolokPricing = getReolokVariationPricingData();
        calculateReolokSystem();
    }

    function calculateReolokSystem() {
        let wallType = $('#wall-type').val() || $('.wall-type').val() || 'RL162';
        if (!wallType && reolokPricing) {
            wallType = Object.keys(reolokPricing)[0] || 'RL162';
        }
        console.log(wallType, 'wallType');
		let wallLabel = $('#wall_label').val() ? $('#wall_label').val().trim() : null;
        
        const length = parseFloat($('#rlLength').val()) || 0;
        const height = parseFloat($('#rlHeight').val()) || 0;
        const endcaps = parseFloat($('#rlEndcaps').val()) || 0;
        if (!reolokPricing[wallType]) {
            $('#addToCart').prop('disabled', true);
            $('#displayTotal').text('Select Wall Type');
            return;
        }

        const pricing = reolokPricing[wallType];

        // const adjustedHeight = height > 0 ? Math.min(ceiling(height, 0.2), 3.6) : 0;
        const cassetteQty = Math.ceil(length / 1.2);
        const endcapLength = ceiling(endcaps, 0.1);
        const bottomTrackLength = cassetteQty * 1.2;
        
        $('#reolokHeight').text(height + ' m');
        $('#reolokCassetteQty').text(formatNumber(cassetteQty));
        $('#reolokEndcap').text(endcapLength.toFixed(2) + ' m');
        $('#reolokBottomTrack').text(bottomTrackLength.toFixed(2) + ' m');

        const cassetteQtyWithWaste = Math.ceil(cassetteQty * (1 + materialWaste));
        const joiningTrussQtyWithWaste = cassetteQtyWithWaste * 2;
        const endcapQty = Math.ceil(endcapLength / 1);
        const bottomTrackQty = Math.ceil(bottomTrackLength / 1);

        const cassettePriceAdjust1 = Math.min(
            ceiling(height / 3.6, 0.5),
            (height + 0.2) / 3.6,
            1
        );
        
        const heightRatio = height / 3.6;
        const cassettePriceAdjust2 = (heightRatio % 1) > fullCassetteThreshold ? 
            Math.ceil(cassettePriceAdjust1) : cassettePriceAdjust1;

        const cassetteUnitPrice = pricing.cassettePrice !== null ? pricing.cassettePrice * cassettePriceAdjust2 : 0;
        const cassetteTotal = cassetteQtyWithWaste * cassetteUnitPrice;
        const casseteUnitVolume = pricing.cassetteVolume * (cassettePriceAdjust2 || 0);
        const casseteTotalVolume = cassetteQtyWithWaste * casseteUnitVolume;

        const joiningTrussUnitPrice = pricing.joiningTrussPrice !== null ? pricing.joiningTrussPrice * cassettePriceAdjust2 : 0;
        const joiningTrussTotal = joiningTrussQtyWithWaste * joiningTrussUnitPrice;
        const joingTrussUnitVolume = pricing.joiningTrussVolume * (cassettePriceAdjust2 || 0);
        const joingTrussTotalVolume = joiningTrussQtyWithWaste * joingTrussUnitVolume;

        const endcapTotal = endcapQty * (pricing.endcapPrice !== null ? pricing.endcapPrice : 0);
        const endcapUnitVolume = pricing.endcapVolume * (cassettePriceAdjust2 || 0);
        const endcapTotalVolume = endcapQty * endcapUnitVolume;
        
        const bottomTrackTotal = bottomTrackQty * bottomTrackPrice;
        const bottomTrackUnitVolume = Number(calculatorPricing.reolok_bottom_track_volume) * (cassettePriceAdjust2 || 0);
        const bottomTrackTotalVolume = bottomTrackQty * bottomTrackUnitVolume;
        
        const showCassette = pricing.cassettePrice !== null && pricing.cassettePrice > 0;
        const showJoiningTruss = pricing.joiningTrussPrice !== null && pricing.joiningTrussPrice > 0;
        const showEndcap = pricing.endcapPrice !== null && pricing.endcapPrice > 0;
        const showBottomTrack = bottomTrackPrice && bottomTrackPrice > 0;

        $('#rlCassetteRow').toggle(showCassette);
        $('#rlJoiningTrussRow').toggle(showJoiningTruss);
        $('#rlEndcapRow').toggle(showEndcap);
        $('#rlBottomTrackRow').toggle(showBottomTrack);

        if (showCassette) {
            $('#rlCassetteQty').text(formatNumber(cassetteQtyWithWaste));
            $('#rlCassettePrice').text(formatCurrency(cassetteUnitPrice));
            $('#rlCassetteTotal').text(formatCurrency(cassetteTotal));
            $('#rlCassetteUnitVolume').text(formatVolume(casseteUnitVolume));
            $('#rlCassetteTotalVolume').text(formatVolume(casseteTotalVolume));
        }

        if (showJoiningTruss) {
            $('#rlJoiningTrussQty').text(formatNumber(joiningTrussQtyWithWaste));
            $('#rlJoiningTrussPrice').text(formatCurrency(joiningTrussUnitPrice));
            $('#rlJoiningTrussTotal').text(formatCurrency(joiningTrussTotal));
            $('#rlJoiningTrussUnitVolume').text(formatVolume(joingTrussUnitVolume));
            $('#rlJoiningTrussTotalVolume').text(formatVolume(joingTrussTotalVolume));
        }

        if (showEndcap) {
            // $('#rlEndcapQty').text(formatNumber(endcapLength);
            $('#rlEndcapQty').text(Math.ceil(endcapLength));
            
            $('#rlEndcapPrice').text(formatCurrency(pricing.endcapPrice));
            $('#rlEndcapTotal').text(formatCurrency(endcapTotal));
            $('#rlEndcapUnitVolume').text(formatVolume(endcapUnitVolume));
            $('#rlEndcapTotalVolume').text(formatVolume(endcapTotalVolume));
        }

        if (showBottomTrack) {
            // $('#rlBottomTrackQty').text(formatNumber(bottomTrackQty));
            $('#rlBottomTrackQty').text(Math.ceil(bottomTrackLength));
            $('#rlBottomTrackPrice').text(formatCurrency(bottomTrackPrice));
            $('#rlBottomTrackTotal').text(formatCurrency(bottomTrackTotal));
            $('#rlBottomTrackUnitVolume').text(formatVolume(bottomTrackUnitVolume));
            $('#rlBottomTrackTotalVolume').text(formatVolume(bottomTrackTotalVolume));
        }

        let materialsTotal = 0;
        if (showCassette) materialsTotal += cassetteTotal;
        if (showJoiningTruss) materialsTotal += joiningTrussTotal;
        if (showEndcap) materialsTotal += endcapTotal;
        if (showBottomTrack) materialsTotal += bottomTrackTotal;
        
        let volumeTotal = 0;
        if (showCassette) volumeTotal += casseteTotalVolume;
        if (showJoiningTruss) volumeTotal += joingTrussTotalVolume;
        if (showEndcap) volumeTotal += endcapTotalVolume;
        if (showBottomTrack) volumeTotal += bottomTrackTotalVolume;

        $('#rlMaterialsTotal').text(formatCurrency(materialsTotal));
        $('#rlMaterialsTotalVolumeM3').text(formatVolume(volumeTotal));
        $('#displayTotal').text(formatCurrency(materialsTotal));
        
        const totalAreaM2 = length * height;
        const m2Rate = materialsTotal / totalAreaM2;
        
        $('#rlTotalArea').text(parseFloat(totalAreaM2).toFixed(2));
        $('#rlM2Rate').text("$" + parseFloat(m2Rate).toFixed(2));

        $('#calculated_price').val(materialsTotal);
        // In your calculateReolokSystem() function, update the calculated_data:
        const calculatedDateObj = {
			wallType: wallType,
            length: length,
            height: height,
            endcaps: endcaps,
			endcapQty: showEndcap ? endcapQty : 0,
			endcapLength: endcapLength.toFixed(2),
            cassetteQty: cassetteQty,
            joiningTrussQty: showJoiningTruss ? joiningTrussQtyWithWaste : 0,
            bottomTrackQty: showBottomTrack ? bottomTrackQty : 0,
			bottomTrack: bottomTrackLength.toFixed(2) + 'm',
            totalPrice: materialsTotal,
			m2Rate: parseFloat(m2Rate).toFixed(2),
            totalAreaM2: parseFloat(totalAreaM2).toFixed(2) + 'm<sup>2</sup>',
            summaryData: {
                height: height + 'm',
                cassetteQty: cassetteQty,
                endcapLength: endcapLength.toFixed(2) + 'm',
                bottomTrack: bottomTrackLength.toFixed(2) + 'm',
            },
            rawSummaryDataReolok: {
                height: height,
                cassetteQty: cassetteQty,
                endcapLength: endcapLength.toFixed(2),
                bottomTrack: bottomTrackLength.toFixed(2),
                m2Rate: parseFloat(m2Rate).toFixed(2),
                totalAreaM2: parseFloat(totalAreaM2).toFixed(2),
				volume: formatVolume(volumeTotal),
            }
        };
		if(wallLabel){
			calculatedDateObj.wallLabel = wallLabel;
		}
        const calculatedData = JSON.stringify(calculatedDateObj);
        // Updated calculated data in hidden input to send it to cart item data
        $('#calculated_data').val(calculatedData);
        
        if (materialsTotal > 0) {
            $('#addToCart').prop('disabled', false);
        } else {
            $('#addToCart').prop('disabled', true);
        }
    }

    function initializeCalculator() {
        const wallType = $('#wall-type').val() || $('.wall-type').val();
        if (wallType) {
            $('.woocommerce-cost-calculator').show();
        }
        
        $('#rlLength, #rlHeight, #rlEndcaps, #wall_label').on('input change', function() {
            calculateReolokSystem();
        });

        $(document).on('change', '#wall-type', function(){
            const wallType = $(this).val();
            const calculator = $('.woocommerce-cost-calculator');
            
            if(wallType){
                calculator.slideDown();
                updateReolokPricingData();

                $('#rlCassetteLabel').text(`${wallType} Cassette`);
                $('#rlJoiningTrussLabel').text(`${wallType} Joining Truss`);
                $('#rlEndcapLabel').text(`${wallType} Endcap (m)`);

            } else {
                calculator.slideUp();
            }


        });

        $('#addToCart').on('click', function(e) {
            e.preventDefault();

            if ($(this).prop('disabled')) {
                alert('Please enter valid dimensions to calculate price');
                return;
            }
            const calculatedPrice = parseFloat($('#calculated_price').val());
            if (calculatedPrice > 0) {
                // Add a small delay to ensure form submission includes our data
                setTimeout(function() {
                    $('form.cart').submit();
                }, 100);
            } else {
                alert('Please calculate a valid price first');
            }
        });
        setTimeout(function() {
            updateReolokPricingData();
            calculateReolokSystem();
        }, 500);
    }

    initializeCalculator();
});
