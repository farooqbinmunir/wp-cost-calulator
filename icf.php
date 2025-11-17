<!-- icf.php -->
<div class="woocommerce-cost-calculator" style="display: none;">
    <div class="page-wrapper">
        <header class="header">
            <h2>FormPro<sup>&reg;</sup> ICF Cost Calculator</h2>
        </header>
        <main>
            <section class="calculator">
                <div class="cwrapper">
                    <div class="cgroup-area">
                        <!-- EE System (ICF) -->
                        <div id="eeCalculator">
                            <table class="calculator-inputs">
                                <thead>
                                    <tr>
                                        <th>Length (m)</th>
                                        <th>Height (m)</th>
                                        <th>Corners</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><input type="number" id="eeLength" value="50" min="0" step="0.01" /></td>
                                        <td><input type="number" id="eeHeight" value="2.4" min="0" step="0.01" /></td>
                                        <td><input type="number" id="eeCorners" value="2" min="0" /></td>
                                    </tr>
                                    <tr>
                                        <th colspan="3" style="text-align: center;">
                                            <div class="opening-toggle">
                                                <span class="toggle-label">Door/Window Openings</span>
                                                <label class="toggle-switch">
                                                    <input type="checkbox" id="eeOpeningToggle" />
                                                    <span class="toggle-slider"></span>
                                                </label>
                                            </div>
                                        </th>
                                    </tr>
                                    <tr class="opening-dimensions" style="display: none;">
                                        <td colspan="3">
                                            <div class="opening-fields">
                                                <div class="dimension-inputs">
                                                    <label>Opening Dimensions</label>
                                                    <div class="input-group">
                                                        <input type="number" id="eeOpeningLength" placeholder="Length" min="0" step="0.1" />
                                                        <span class="dimension-separator">×</span>
                                                        <input type="number" id="eeOpeningHeight" placeholder="Height" min="0" step="0.1" />
                                                        <span class="dimension-equals">=</span>
                                                        <span class="opening-area-result">0.00 m²</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
							<!-- Wall label/description -->
							<div class="wall_label_field">
									<label for="wall_label">Wall Label / Description</label>
									<input type="text" id="wall_label" name="wall_label" placeholder="Enter wall label or description" />
							</div>

                            
                            <!-- Area Summary - Above the table -->
                            <div class="area-summary">
                                <div class="area-item">
                                    <span class="area-label">Total Area (m<sup>2</sup>)</span>
                                    <span class="area-value" id="totalAreaM2">0.00</span>
                                </div>
                                <div class="area-item">
                                    <span class="area-label">m<sup>2</sup> Rate</span>
                                    <span class="area-value" id="m2Rate">$0.00</span>
                                </div>
                            </div>
							<div class="cc_details_table">
								<table class="details-table">
									<thead>
										<tr>
											<th>Item</th>
											<th>Quantity</th>
											<th>Unit Price</th>
											<th>Total</th>
											<th class="wise_temp_hidden">Unit Volume</th>
											<th class="wise_temp_hidden">Total (m<sup>3</sup>)</th>
										</tr>
									</thead>
									<tbody>
										<tr>
											<td>Full Blocks</td>
											<td id="eeFullBlocks">0</td>
											<td id="eeFullBlocksPrice">$0.00</td>
											<td id="eeFullBlocksTotal">$0.00</td>
											<td id="eeFullBlocksUnitVolume" class="wise_temp_hidden">$0.00</td>
											<td id="eeFullBlocksTotalVolume" class="wise_temp_hidden">$0.00</td>
										</tr>
										<tr>
											<td>Corner Blocks</td>
											<td id="eeCnrBlocks">0</td>
											<td id="eeCnrBlocksPrice">$0.00</td>
											<td id="eeCnrBlocksTotal">$0.00</td>
											<td id="eeCnrBlocksUnitVolume" class="wise_temp_hidden">$0.00</td>
											<td id="eeCnrBlocksTotalVolume" class="wise_temp_hidden">$0.00</td>
										</tr>
										<tr>
											<td>C Channel (m)</td>
											<td id="eeCChannel">0</td>
											<td id="eeCChannelPrice">$0.00</td>
											<td id="eeCChannelTotal">$0.00</td>
											<td id="eeCChannelUnitVolume" class="wise_temp_hidden">$0.00</td>
											<td id="eeCChannelTotalVolume" class="wise_temp_hidden">$0.00</td>
										</tr>
										<tr class="c_calculator_row_braces">
											<td>Braces</td>
											<td id="eeBraces">0</td>
											<td id="eeBracesPrice">$0.00</td>
											<td id="eeBracesTotal">$0.00</td>
										</tr>
										<tr>
											<td><strong>Total</strong></td>
											<td colspan="2"></td>
											<td id="eeSubtotal">$0.00</td>
											<td class="wise_temp_hidden"></td>
											<td id="eeTotalVolumeM3" class="wise_temp_hidden">$0.00</td>
										</tr>
									</tbody>
								</table>
                            </div>
                            <div class="calculator-actions">
                                <button type="button" id="addToCart" class="single_add_to_cart_button button alt" disabled>
                                    Add to Cart <span id="displayTotal">$0.00</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>
</div>