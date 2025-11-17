<!-- reolok.php -->
<div class="woocommerce-cost-calculator" style="display: none;">
    <div class="page-wrapper">
        <header class="header">
            <h2>Reolok Cost Calculator</h2>
        </header>
        <main>
            <section class="calculator">
                <div class="cwrapper">
                    <div class="cgroup-area">
                        <!-- Reolok System -->
                        <div id="reolokCalculator">
                            <!-- 1. Input Fields -->
                            <table class="calculator-inputs">
                                <thead>
                                    <tr>
                                        <th>Length (m)</th>
                                        <th>Height (m)</th>
                                        <th>Endcaps (m)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><input type="number" id="rlLength" value="13" min="0" step="0.01" /></td>
                                        <td><input type="number" id="rlHeight" value="2.8" min="0" step="0.01" max="3.6" /></td>
                                        <td><input type="number" id="rlEndcaps" value="10" min="0" step="0.01" /></td>
                                    </tr>
                                </tbody>
                            </table>
							
							<!-- Wall label/description -->
							<div class="wall_label_field">
									<label for="wall_label">Wall Label / Description</label>
									<input type="text" id="wall_label" name="wall_label" placeholder="Enter wall label or description" />
							</div>
                            
                            <!-- 2. Adjusted Height Section -->
                            <div class="area-summary">
								<div class="area-item">
                                    <span class="area-label">Total Area(m<sup>2</sup>)</span>
                                    <span class="area-value" id="rlTotalArea">0.00</span>
                                </div>
								<div class="area-item">
                                    <span class="area-label">m<sup>2</sup> Rate</span>
                                    <span class="area-value" id="rlM2Rate">$0.00</span>
                                </div>
                                <div class="area-item wise_temp_hidden">
                                    <span class="area-label">Height</span>
                                    <span class="area-value" id="reolokHeight">0.00 m</span>
                                </div>
                                <div class="area-item wise_temp_hidden">
                                    <span class="area-label">Cassette Qty</span>
                                    <span class="area-value" id="reolokCassetteQty">0</span>
                                </div>
                                <div class="area-item wise_temp_hidden">
                                    <span class="area-label">Endcap Length</span>
                                    <span class="area-value" id="reolokEndcap">0.00 m</span>
                                </div>
                                <div class="area-item wise_temp_hidden">
                                    <span class="area-label">Bottom Track</span>
                                    <span class="area-value" id="reolokBottomTrack">0.00 m</span>
                                </div>
                            </div>
                            
                            <!-- 3. Materials Total Section -->
							<div class="cc_details_table">
								<table class="details-table">
									<thead>
										<tr>
											<th>Item</th>
											<th>Quantity</th>
											<th>Unit Price</th>
											<th>Total Price</th>
											<th class="wise_temp_hidden">Unit Volume</th>
											<th class="wise_temp_hidden">Total (m<sup>3</sup>)</th>
										</tr>
									</thead>
									<tbody>
										<tr id="rlCassetteRow">
											<td id="rlCassetteLabel">RL162 Cassette</td>
											<td id="rlCassetteQty">0</td>
											<td id="rlCassettePrice">$0.00</td>
											<td id="rlCassetteTotal">$0.00</td>
											<td id="rlCassetteUnitVolume" class="wise_temp_hidden">$0.00</td>
											<td id="rlCassetteTotalVolume" class="wise_temp_hidden">$0.00</td>
										</tr>
										<tr id="rlJoiningTrussRow">
											<td id="rlJoiningTrussLabel">RL162 Joining Truss</td>
											<td id="rlJoiningTrussQty">0</td>
											<td id="rlJoiningTrussPrice">$0.00</td>
											<td id="rlJoiningTrussTotal">$0.00</td>
											<td id="rlJoiningTrussUnitVolume" class="wise_temp_hidden">$0.00</td>
											<td id="rlJoiningTrussTotalVolume" class="wise_temp_hidden">$0.00</td>
										</tr>
										<tr id="rlEndcapRow">
											<td id="rlEndcapLabel">RL162 Endcap (m)</td>
											<td id="rlEndcapQty">0</td>
											<td id="rlEndcapPrice">$0.00</td>
											<td id="rlEndcapTotal">$0.00</td>
											<td id="rlEndcapUnitVolume" class="wise_temp_hidden">$0.00</td>
											<td id="rlEndcapTotalVolume" class="wise_temp_hidden">$0.00</td>
										</tr>
										<tr id="rlBottomTrackRow">
											<td id="rlBottomTrackLabel">ReoLok Bottom Track (m)</td>
											<td id="rlBottomTrackQty">0</td>
											<td id="rlBottomTrackPrice">$0.00</td>
											<td id="rlBottomTrackTotal">$0.00</td>
											<td id="rlBottomTrackUnitVolume" class="wise_temp_hidden">0.00</td>
											<td id="rlBottomTrackTotalVolume" class="wise_temp_hidden">0.00</td>
										</tr>
										<tr>
											<td><strong>Materials Total</strong></td>
											<td colspan="2"></td>
											<td id="rlMaterialsTotal">$0.00</td>
											<td class="wise_temp_hidden"></td>
											<td id="rlMaterialsTotalVolumeM3" class="wise_temp_hidden">0.00</td>
										</tr>
									</tbody>
								</table>
							</div>
                            
                            <!-- 4. Add to Cart Button -->
                            <div class="calculator-actions">
                                <button type="button" id="addToCart" class="single_add_to_cart_button button alt" disabled>
                                    Add to Cart  <span id="displayTotal">$0.00</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>
</div>