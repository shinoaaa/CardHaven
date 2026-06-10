<div id="productModal" class="modal-overlay">
    <div class="modal-box" style="width: 650px;">
        <div class="modal-header">
            <h2 id="pTitle">ADD <span class="blue-text">PRODUCT</span></h2>
            <span id="pDisplayID" class="game-id"></span>
        </div>
        <form id="productForm">
            <input type="hidden" name="action" id="pAction" value="add">
            <input type="hidden" name="id_produk" id="pID">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="modal-form-group">
                    <label>Product Name <span style="color: #E74C3C;">*</span></label>
                    <input type="text" name="nama_produk" id="pNama" class="modal-input" placeholder="Rayquaza V...">
                    <span class="error-message"></span>
                </div>
                <div class="modal-form-group">
                    <label>Product Type <span style="color: #E74C3C;">*</span></label>
                    <select name="tipe_produk" id="pTipe" class="modal-input" onchange="toggleProdFields()">
                        <option value="">-- Select Product Type --</option>
                        <option value="Single Card">Single Card</option>
                        <option value="Booster Pack">Booster Pack</option>
                        <option value="Booster Box">Booster Box</option>
                        <option value="Sleeve">Sleeve</option>
                        <option value="Playmat">Playmat</option>
                    </select>
                    <div class="error-message"></div>
                </div>
            </div>

            <div class="modal-form-group">
                <label>Game <span style="color: #E74C3C;">*</span></label>
                <div style="position:relative;">
                    <input type="text" id="pGameSearch" class="modal-input" placeholder="Type game name..." autocomplete="off">
                    <input type="hidden" name="id_game" id="pIdGame">
                    <div id="pGameSuggest" class="suggestion-box"></div>
                </div>
                <div class="error-message"></div>
            </div>

            <div id="extraFields" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="modal-form-group" id="pSetGroup">
                    <label>Card Set <span style="color: #E74C3C;">*</span></label>
                    <div style="position:relative;">
                        <input type="text" id="pSetSearch" class="modal-input" placeholder="Search set..." autocomplete="off">
                        <input type="hidden" name="id_set" id="pIdSet">
                        <div id="pSetSuggest" class="suggestion-box"></div>
                    </div>
                    <div class="error-message"></div>
                </div>
                <div class="modal-form-group" id="pRarityGroup">
                    <label>Rarity <span style="color: #E74C3C;">*</span></label>
                    <select name="id_rarity" id="pIdRarity" class="modal-input">
                        <option value="">-- Select Rarity --</option>
                    </select>
                    <div class="error-message"></div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 10px;">
                <div class="modal-form-group" id="pKondisiGroup">
                    <label>Condition <span style="color: #E74C3C;">*</span></label>
                    <select name="kondisi" id="pKondisi" class="modal-input">
                        <option value="">-- Condition --</option>
                        <option value="M">Mint</option>
                        <option value="NM">Near Mint</option>
                        <option value="LP">Lightly Played</option>
                        <option value="MP">Moderately Played</option>
                        <option value="HP">Heavily Played</option>
                        <option value="DMG">Damaged</option>
                    </select>
                    <div class="error-message"></div>
                </div>
                <div class="modal-form-group">
                    <label>Stock <span style="color: #E74C3C;">*</span></label>
                    <input type="number" min="1" name="stok" id="pStok" class="modal-input">
                    <div class="error-message"></div>
                </div>
                <div class="modal-form-group">
                    <label>Buy (Rp) <span style="color: #E74C3C;">*</span></label>
                    <input type="number" min="0" name="harga_beli" id="pBeli" class="modal-input">
                    <div class="error-message"></div>
                </div>
                <div class="modal-form-group">
                    <label>Sell (Rp) <span style="color: #E74C3C;">*</span></label>
                    <input type="number" min="0" name="harga_jual" id="pJual" class="modal-input">
                    <div class="error-message"></div>
                </div>
            </div>
            <div class="modal-form-group">
                <label>Description (Optional)</label>
                <textarea name="deskripsi" id="pDeskripsi" class="modal-input" rows="3" placeholder="Additional details..."></textarea>
                <div class="error-message"></div>
            </div>

            <div id="pLogSection" style="display:none; margin-top:15px;">
                <div class="modal-form-group">
                    <label>Created By</label>
                    <div class="log-display">
                        <span id="pCreatedBy"></span>
                        <span id="pCreatedDate"></span>
                    </div>
                </div>
                <div class="modal-form-group">
                    <label>Edited By</label>
                    <div class="log-display">
                        <span id="pEditedBy"></span>
                        <span id="pEditedDate"></span>
                    </div>
                </div>
                <div class="status-text">Current Status: <span id="pStatusLabel"></span></div>
                <input type="hidden" name="status" id="pStatusValue">
            </div>
            <div class="modal-form-group">
            <label>Product Image <span style="color:#888; font-size:0.85em;"></span></label>
            <div style="text-align: center; margin-bottom: 8px;">
                <img id="pPreview" src="" style="display:none; max-width:100%; max-height:150px; border-radius:8px; object-fit:contain;">
                <div id="pPlaceholder" style="color:#aaa; padding: 20px; border: 2px dashed #ccc; border-radius:8px; font-size:0.9rem;">No image selected</div>
            </div>
            <input type="file" name="foto_produk" id="pFoto" class="modal-input" accept="image/jpeg,image/png,image/webp,image/svg+xml" onchange="previewImage(this)">
            <span id="error-foto" class="error-message"></span>
        </div>
            <button type="submit" class="btn-confirm">Confirm Product</button>
        </form>
    </div>
</div>

<div id="gameModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <h2 id="modalTitle">ADD <span class="blue-text">GAME</span></h2>
            <span id="displayID" class="game-id"></span>
        </div>

        <form id="gameForm">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id_game" id="formID">

            <div class="modal-form-group">
                <label>Game Name <span style="color: #E74C3C;">*</span></label>
                <input type="text" name="nama_game" id="nama_game" class="modal-input" placeholder="Enter Game Name...">
                <div class="error-message"></div>
            </div>

            <div class="modal-form-group">
                <label id="labelDev">Developer Name <span style="color: #E74C3C;">*</span></label>
                <input type="text" name="developer" id="developer" class="modal-input" placeholder="Enter Developer Name...">
                <div class="error-message"></div>
            </div>

            <button type="submit" class="btn-confirm">Confirm</button>
        </form>
    </div>
</div>

<div id="gameDetailModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <h2>GAME <span class="blue-text">DETAIL</span></h2>
            <span id="gameDetailDisplayID" class="game-id"></span>
        </div>

        <div class="modal-form-group">
            <label>Game Name</label>
            <div class="detail-field" id="detailGameNama">-</div>
        </div>

        <div class="modal-form-group">
            <label>Developer</label>
            <div class="detail-field" id="detailGameDev">-</div>
        </div>

        <div class="modal-form-group">
            <label>Status</label>
            <div class="detail-field" id="detailGameStatus">-</div>
        </div>

        <button class="btn-confirm" onclick="document.getElementById('gameDetailModal').style.display='none'">Close</button>
    </div>
</div>

<div id="rarityModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <h2 id="modalTitleRarity">ADD <span class="blue-text">RARITY</span></h2>
            <span id="displayIDRarity" class="game-id"></span>
        </div>

        <form id="rarityForm">
            <input type="hidden" name="action" id="formActionRarity">
            <input type="hidden" name="id_rarity" id="inputIdRarity">
            <input type="hidden" name="aktif" id="inputAktifRarity" value="1">

            <div class="modal-form-group">
                <label>Game <span style="color: #E74C3C;">*</span></label>
                <select id="inputGameRarity" name="id_game" class="modal-input">
                    <option value="">-- Select Game --</option>
                    <?php
                    $sql_dropdown_r = "SELECT id_game, nama_game FROM dbo.game WHERE aktif = 1 ORDER BY nama_game ASC";
                    $stmt_dropdown_r = sqlsrv_query($conn, $sql_dropdown_r);
                    if ($stmt_dropdown_r) {
                        while ($rowGame = sqlsrv_fetch_array($stmt_dropdown_r, SQLSRV_FETCH_ASSOC)) {
                            echo '<option value="' . htmlspecialchars($rowGame['id_game']) . '">' . htmlspecialchars($rowGame['nama_game']) . '</option>';
                        }
                    }
                    ?>
                </select>
                <div class="error-message"></div>
            </div>

            <div class="modal-form-group">
                <label>Rarity Name <span style="color: #E74C3C;">*</span></label>
                <input type="text" id="inputNamaRarity" name="nama_rarity" class="modal-input" placeholder="Enter Rarity Name...">
                <div class="error-message"></div>
            </div>

            <div class="modal-form-group">
                <label>Rarity Code <span style="color: #E74C3C;">*</span></label>
                <input type="text" id="inputKodeRarity" name="kode_rarity" class="modal-input">
                <div class="error-message"></div>
            </div>

            <button type="submit" class="btn-confirm">Confirm</button>
        </form>
    </div>
</div>

<div id="rarityDetailModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <h2>RARITY <span class="blue-text">DETAIL</span></h2>
            <span id="rarityDetailDisplayID" class="game-id"></span>
        </div>

        <div class="modal-form-group">
            <label>Game</label>
            <div class="detail-field" id="detailRarityGame">-</div>
        </div>

        <div class="modal-form-group">
            <label>Rarity Name</label>
            <div class="detail-field" id="detailRarityNama">-</div>
        </div>

        <div class="modal-form-group">
            <label>Rarity Code</label>
            <div class="detail-field" id="detailRarityKode">-</div>
        </div>

        <div class="modal-form-group">
            <label>Status</label>
            <div class="detail-field" id="detailRarityStatus">-</div>
        </div>

        <button class="btn-confirm" onclick="document.getElementById('rarityDetailModal').style.display='none'">Close</button>
    </div>
</div>

<div id="setModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <h2 id="setModalTitle">ADD <span class="blue-text">SET</span></h2>
            <span id="setDisplayID" class="game-id"></span>
        </div>

        <form id="setForm">
            <input type="hidden" name="action" id="setFormAction" value="add">
            <input type="hidden" name="id_set" id="setIdInput">

            <div class="modal-form-group">
                <label>Game <span style="color:#E74C3C;">*</span></label>
                <select name="id_game" id="setGameId" class="modal-input">
                    <option value="">-- Select Game --</option>
                </select>
                <div class="error-message"></div>
            </div>

            <div class="modal-form-group">
                <label>Set Name <span style="color:#E74C3C;">*</span></label>
                <input type="text" name="nama_set" id="setNama" class="modal-input" placeholder="Enter Set Name...">
                <div class="error-message"></div>
            </div>

            <div class="modal-form-group">
                <label>Set Code <span style="color:#E74C3C;">*</span></label>
                <input type="text" name="kode_set" id="setKode" class="modal-input" placeholder="e.g. SV-001">
                <div class="error-message"></div>
            </div>

            <div class="modal-form-group">
                <label>Release Date <span style="color:#888; font-size:0.85em;">(Optional)</span></label>
                <input type="date" name="tanggal_rilis" id="setTanggal" class="modal-input">
                <div class="error-message"></div>
            </div>

            <button type="submit" class="btn-confirm">SAVE</button>
        </form>
    </div>
</div>

<div id="setDetailModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <h2>SET <span class="blue-text">DETAIL</span></h2>
            <span id="setDetailDisplayID" class="game-id"></span>
        </div>

        <div class="modal-form-group">
            <label>Set Name</label>
            <div class="detail-field" id="detailSetNama">-</div>
        </div>

        <div class="modal-form-group">
            <label>Set Code</label>
            <div class="detail-field" id="detailSetKode">-</div>
        </div>

        <div class="modal-form-group">
            <label>Game</label>
            <div class="detail-field" id="detailSetGame">-</div>
        </div>

        <div class="modal-form-group">
            <label>Release Date</label>
            <div class="detail-field" id="detailSetTanggal">-</div>
        </div>

        <div class="modal-form-group">
            <label>Status</label>
            <div class="detail-field" id="detailSetStatus">-</div>
        </div>

        <button class="btn-confirm" onclick="document.getElementById('setDetailModal').style.display='none'">Close</button>
    </div>
</div>

<div id="metodeModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <h2 id="metodeModalTitle">ADD <span class="blue-text">PAYMENT METHOD</span></h2>
            <span id="metodeDisplayID" class="game-id"></span>
        </div>

        <form id="metodeForm">
            <input type="hidden" name="action"    id="metodeFormAction" value="add">
            <input type="hidden" name="id_metode" id="metodeIdInput">
            <input type="hidden" name="aktif"     id="metodeAktifStatus" value="1">

            <div class="modal-form-group">
                <label>Method Name <span style="color:#E74C3C;">*</span></label>
                <input type="text" name="nama_metode" id="metodeNama" class="modal-input" placeholder="e.g. GoPay, QRIS, BCA Transfer">
                <div class="error-message"></div>
            </div>

            <div class="modal-form-group">
                <label>Provider <span style="color:#E74C3C;">*</span></label>
                <input type="text" name="provider" id="metodeProvider" class="modal-input" placeholder="e.g. GoPay, Bank BCA">
                <div class="error-message"></div>
            </div>

            <div class="modal-form-group">
                <label>Account Number <span style="color:#E74C3C;">*</span></label>
                <input type="text" name="no_rekening" id="metodeNoRek" class="modal-input" placeholder="e.g. 081234567890">
                <div class="error-message"></div>
            </div>

            <div class="modal-form-group">
                <label>Account Name <span style="color:#E74C3C;">*</span></label>
                <input type="text" name="atas_nama" id="metodeAtasNama" class="modal-input" placeholder="e.g. CardHaven Store">
                <div class="error-message"></div>
            </div>

            <div class="modal-form-group">
                <label>Admin Fee (Rp) <span style="color:#E74C3C;">*</span></label>
                <input type="number" name="biaya_admin" id="metodeBiaya" class="modal-input" placeholder="e.g. 2000" min="0" value="0">
                <div class="error-message"></div>
            </div>

            <button type="submit" class="btn-confirm">SAVE</button>
        </form>
    </div>
</div>

<div id="metodeDetailModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <h2>PAYMENT METHOD <span class="blue-text">DETAIL</span></h2>
            <span id="metodeDetailDisplayID" class="game-id"></span>
        </div>

        <div class="modal-form-group">
            <label>Method Name</label>
            <div class="detail-field" id="detailMetodeNama">-</div>
        </div>

        <div class="modal-form-group">
            <label>Provider</label>
            <div class="detail-field" id="detailMetodeProvider">-</div>
        </div>

        <div class="modal-form-group">
            <label>Account Number</label>
            <div class="detail-field" id="detailMetodeNoRek">-</div>
        </div>

        <div class="modal-form-group">
            <label>Account Name</label>
            <div class="detail-field" id="detailMetodeAtasNama">-</div>
        </div>

        <div class="modal-form-group">
            <label>Admin Fee</label>
            <div class="detail-field" id="detailMetodeBiaya">-</div>
        </div>

        <div class="modal-form-group">
            <label>Status</label>
            <div class="detail-field" id="detailMetodeStatus">-</div>
        </div>

        <button class="btn-confirm" onclick="document.getElementById('metodeDetailModal').style.display='none'">Close</button>
    </div>
</div>