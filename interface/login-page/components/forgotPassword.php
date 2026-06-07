<div class="form-container">
    <h1 class="coolvetica">
        Forgot <span style="color: var(--highlight); letter-spacing: 0px;">Password</span>
    </h1>

    <form id="forgotForm" novalidate>
        <div class="form-group">
            <label>Email</label>
            <input type="email" id="forgot-email" name="email" placeholder="enter email..." required>
            <small id="forgot-error-email" class="error-message"></small>
        </div>

        <div class="form-group">
            <label>Created Date</label>
            <input type="date" id="forgot-created-date" name="created_date" required>
            <small id="forgot-error-created-date" class="error-message"></small>
        </div>

        <div id="password-section" style="display: none;">
            <div class="form-group">
                <label>New Password</label>
                <input type="password" id="forgot-password" name="password" placeholder="enter new password...">
                <small id="forgot-error-password" class="error-message"></small>
            </div>

            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" id="forgot-confirm-password" name="confirm_password" placeholder="confirm new password...">
                <small id="forgot-error-confirm-password" class="error-message"></small>
            </div>
        </div>

        <button type="submit" id="forgot-submit" class="btn-confirm">Verify</button>
    </form>

    <div style="margin-top: 15px;">
        <a id="back-to-login" style="color: #0088FF; font-size: 13px; cursor: pointer; ">
            Return to login page
        </a>
    </div>
</div>