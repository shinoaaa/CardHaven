<div id="productModal" class="modal-overlay">
    <div class="modal-box" style="width: 650px;">
        <div class="modal-header">
            <h2 id="pTitle">ADD <span class="blue-text">PRODUCT</span></h2>
            <span id="pDisplayID" class="game-id"></span>
        </div>

        <form id="productForm">
            <input type="hidden" name="action" id="pAction" value="add">
            <input type="hidden" name="id_produk" id="pID">

            <!-- Baris 1: Nama & Tipe -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="modal-form-group">
                    <label>Product Name</label>
                    <input type="text" name="nama_produk" id="pNama" class="modal-input" placeholder="Rayquaza V...">
                    <span class="error-message"></span>
                </div>
                <div class="modal-form-group">
                    <label>Product Type</label>
                    <select name="tipe_produk" id="pTipe" class="modal-input" onchange="toggleProdFields()">
                        <option value="Single Card">Single Card</option>
                        <option value="Booster Pack">Booster Pack</option>
                        <option value="Booster Box">Booster Box</option>
                        <option value="Sleeve">Sleeve</option>
                        <option value="Playmat">Playmat</option>
                    </select>
                </div>
            </div>

            <!-- Baris 2: Game (Suggestion) -->
            <div class="modal-form-group">
                <label>Game</label>
                <div style="position:relative;">
                    <input type="text" id="pGameSearch" class="modal-input" placeholder="Type game name..." autocomplete="off">
                    <input type="hidden" name="id_game" id="pIdGame">
                    <div id="pGameSuggest" class="suggestion-box"></div>
                </div>
                <div class="error-message"></div>
            </div>

            <!-- Baris 3: Set (Suggestion) & Rarity (Dropdown) -->
            <div id="extraFields" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="modal-form-group" id="pSetGroup">
                    <label>Card Set</label>
                    <div style="position:relative;">
                        <input type="text" id="pSetSearch" class="modal-input" placeholder="Search set..." autocomplete="off">
                        <input type="hidden" name="id_set" id="pIdSet">
                        <div id="pSetSuggest" class="suggestion-box"></div>
                    </div>
                    <div class="error-message"></div>
                </div>
                <div class="modal-form-group" id="pRarityGroup">
                    <label>Rarity</label>
                    <select name="id_rarity" id="pIdRarity" class="modal-input">
                        <option value="">-- Select Rarity --</option>
                    </select>
                    <div class="error-message"></div>
                </div>
            </div>

            <!-- Baris 4: Kondisi, Stok, Harga -->
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 10px;">
                <div class="modal-form-group" id="pKondisiGroup">
                    <label>Condition</label>
                    <select name="kondisi" id="pKondisi" class="modal-input">
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
                    <label>Stock</label>
                    <input type="number" min="0" name="stok" id="pStok" class="modal-input">
                    <div class="error-message"></div>
                </div>
                <div class="modal-form-group">
                    <label>Buy (Rp)</label>
                    <input type="number" min="0" name="harga_beli" id="pBeli" class="modal-input">
                    <div class="error-message"></div>
                </div>
                <div class="modal-form-group">
                    <label>Sell (Rp)</label>
                    <input type="number" min="0" name="harga_jual" id="pJual" class="modal-input">
                    <div class="error-message"></div>
                </div>
            </div>

            <div id="pLogSection" style="display:none; margin-top:15px;">
                <div class="log-display"><span id="pCreatedBy"></span></div>
                <div class="status-text">Current Status: <span id="pStatusLabel"></span></div>
                <input type="hidden" name="status" id="pStatusValue">
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
                <label>Game Name</label>
                <input type="text" name="nama_game" id="nama_game" class="modal-input" placeholder="Enter Game Name..." required>
                <div class="error-message"></div>
            </div>

            <div class="modal-form-group">
                <label id="labelDev">Developer Name</label>
                <input type="text" name="developer" id="developer" class="modal-input" placeholder="Enter Developer Name..." required>
                <div class="error-message"></div>
            </div>

            <div id="logSection" style="display:none;">
                <div class="modal-form-group">
                    <label>Created By</label>
                    <div class="log-display">
                        <span id="createdBy"></span>
                        <span id="createdDate"></span>
                    </div>
                </div>
                <div class="modal-form-group">
                    <label>Edited By</label>
                    <div class="log-display">
                        <span id="editedBy"></span>
                        <span id="editedDate"></span>
                    </div>
                </div>
                <div class="status-text">
                    This game status is currently <span id="statusLabel"></span>
                    <input type="hidden" name="aktif" id="aktifStatus">
                </div>
            </div>

            <button type="submit" class="btn-confirm">Confirm</button>
        </form>
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

            <div class="modal-form-group">
                <label>Game</label>
                <select id="inputGameRarity" name="id_game" class="modal-input" required>
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
            </div>

            <div class="modal-form-group">
                <label>Rarity Name</label>
                <input type="text" id="inputNamaRarity" name="nama_rarity" class="modal-input" required>
                <div class="error-message"></div>
            </div>

            <div class="modal-form-group">
                <label>Rarity Code</label>
                <input type="text" id="inputKodeRarity" name="kode_rarity" class="modal-input">
                <div class="error-message"></div>
            </div>

            <div id="logSectionRarity" style="display:none;">
                <div class="modal-form-group">
                    <label>Created By</label>
                    <div class="log-display">
                        <span id="createdByRarity"></span>
                        <span id="createdDateRarity"></span>
                    </div>
                </div>
                <div class="modal-form-group">
                    <label>Edited By</label>
                    <div class="log-display">
                        <span id="editedByRarity"></span>
                        <span id="editedDateRarity"></span>
                    </div>
                </div>
                <div class="status-text">
                    Status: <span id="statusLabelRarity"></span>
                    <input type="hidden" name="aktif" id="aktifStatusRarity">
                </div>
            </div>

            <button type="submit" class="btn-confirm">Confirm</button>
        </form>
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
                <label>Game</label>
                <select name="id_game" id="setGameId" class="modal-input" required>
                    <option value="">-- Pilih Game --</option>
                    <?php
                    $sql_dropdown_s = "SELECT id_game, nama_game FROM dbo.game WHERE aktif = 1 ORDER BY nama_game ASC";
                    $stmt_dropdown_s = sqlsrv_query($conn, $sql_dropdown_s);
                    if ($stmt_dropdown_s) {
                        while ($g = sqlsrv_fetch_array($stmt_dropdown_s, SQLSRV_FETCH_ASSOC)) {
                            echo "<option value='" . htmlspecialchars($g['id_game']) . "'>" . htmlspecialchars($g['nama_game']) . "</option>";
                        }
                    }
                    ?>
                </select>
            </div>

            <div class="modal-form-group">
                <label>Set Name</label>
                <input type="text" name="nama_set" id="setNama" class="modal-input" placeholder="Enter Set Name..." required>
                <div class="error-message"></div>
            </div>

            <div class="modal-form-group">
                <label>Set Code</label>
                <input type="text" name="kode_set" id="setKode" class="modal-input" placeholder="e.g. SV-01" required>
                <div class="error-message"></div>
            </div>
            <div class="modal-form-group">
                <label>Release Date</label>
                <input type="date" name="tanggal_rilis" id="setTanggal" class="modal-input">
                <div class="error-message"></div>
            </div>

            <div id="setLogSection" style="display:none;">
                <div class="modal-form-group">
                    <label>Created By</label>
                    <div class="log-display">
                        <span id="setCreatedBy"></span>
                        <span id="setCreatedDate"></span>
                    </div>
                </div>
                <div class="modal-form-group">
                    <label>Edited By</label>
                    <div class="log-display">
                        <span id="setEditedBy"></span>
                        <span id="setEditedDate"></span>
                    </div>
                </div>
                <div class="status-text">
                    Status: <span id="setStatusLabel"></span>
                    <input type="hidden" name="aktif" id="setAktifStatus">
                </div>
            </div>

            <button type="submit" class="btn-confirm">Confirm</button>
        </form>
    </div>
</div>