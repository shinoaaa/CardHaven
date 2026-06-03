<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Set - CardHaven</title>
    <link rel="stylesheet" href="/cardhaven/interface/global.css">
    <link rel="stylesheet" href="/cardhaven/interface/master-set/style.css">
</head>
<body>
    <div class="page-container">
        <div class="page-header">
            <h2 class="coolvetica">Master Set</h2>
            <button class="btn-add" id="btnOpenModal">+ Add Set</button>
        </div>

        <!-- Filter & Search -->
        <div class="toolbar">
            <input type="text" id="searchInput" placeholder="Search set name or code..." class="search-input">
            <select id="filterGame" class="filter-select">
                <option value="">All Games</option>
            </select>
            <select id="filterStatus" class="filter-select">
                <option value="">All Status</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
            </select>
        </div>

        <!-- Table -->
        <div class="table-wrapper">
            <table class="data-table" id="setTable">
                <thead>
                    <tr>
                        <th>Set Name</th>
                        <th>Set ID</th>
                        <th>Game</th>
                        <th>Release Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="setTableBody">
                    <tr>
                        <td colspan="6" class="loading-row">Loading...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="pagination" id="pagination"></div>
    </div>

    <!-- Modal Add / Edit Set -->
    <div class="modal-overlay" id="modalOverlay">
        <div class="modal-box">
            <div class="modal-header">
                <h3 class="coolvetica" id="modalTitle">Add New Set</h3>
                <button class="btn-close" id="btnCloseModal">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editIdSet">

                <div class="form-group">
                    <label>Set Name <span class="required">*</span></label>
                    <input type="text" id="inputNamaSet" placeholder="e.g. Scarlet & Violet Prismatic...">
                    <small id="errNamaSet" class="err-msg"></small>
                </div>

                <div class="form-group">
                    <label>Set Code <span class="required">*</span></label>
                    <input type="text" id="inputKodeSet" placeholder="e.g. SET-001" maxlength="20">
                    <small id="errKodeSet" class="err-msg"></small>
                </div>

                <div class="form-group">
                    <label>Game <span class="required">*</span></label>
                    <select id="inputGame">
                        <option value="">-- Select Game --</option>
                    </select>
                    <small id="errGame" class="err-msg"></small>
                </div>

                <div class="form-group">
                    <label>Release Date</label>
                    <input type="date" id="inputTanggalRilis">
                </div>

                <div class="form-group" id="groupStatus" style="display:none;">
                    <label>Status</label>
                    <select id="inputStatus">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>

                <small id="errGeneral" class="err-msg" style="display:block; margin-bottom: 8px;"></small>
                <button class="btn-submit" id="btnSubmit">Save</button>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi Delete -->
    <div class="modal-overlay" id="modalDelete">
        <div class="modal-box modal-sm">
            <div class="modal-header">
                <h3 class="coolvetica">Confirm Delete</h3>
                <button class="btn-close" id="btnCloseDelete">&times;</button>
            </div>
            <div class="modal-body">
                <p id="deleteMsg">Are you sure you want to deactivate this set?</p>
                <div class="modal-actions">
                    <button class="btn-cancel" id="btnCancelDelete">Cancel</button>
                    <button class="btn-danger" id="btnConfirmDelete">Deactivate</button>
                </div>
            </div>
        </div>
    </div>

    <script src="/cardhaven/interface/master-set/script.js"></script>
</body>
</html>
