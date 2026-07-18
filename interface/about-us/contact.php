<div style="min-height: 100vh; display: flex; flex-direction: column; justify-content: space-between; background-color: var(--bg-light);">
    <div style="padding: 8rem 4rem 4rem 4rem; display: flex; justify-content: center; flex: 1;">
        <div class="content-card" style="width: 100%; max-width: 1000px; display: flex; flex-direction: row; gap: 4rem; min-height: auto; padding: 3rem;">
            
            <div style="flex: 1; display: flex; flex-direction: column; justify-content: center;">
                <h1 style="color: var(--primary-color); font-size: 3rem; margin-bottom: 1rem;" class="coolvetica">Contact Us</h1>
                <p style="color: var(--paragraf); line-height: 1.8; font-size: 1.1rem; margin-bottom: 2rem;">
                    Have questions, feedback, or need assistance with your cards? Drop us a message, and the CardHaven team will get back to you as soon as possible!
                </p>
                <div style="display: flex; flex-direction: column; gap: 1rem; color: var(--paragraf); font-size: 0.95rem;">
                    <p><strong style="color: var(--primary-color);">Email:</strong> support@card-haven.com</p>
                    <p><strong style="color: var(--primary-color);">Phone:</strong> +62 812 3456 7890</p>
                    <p><strong style="color: var(--primary-color);">Address:</strong> CardHaven Main Store, Jakarta, Indonesia</p>
                </div>
            </div>

            <div style="flex: 1; background: #f8faff; padding: 2rem; border-radius: 20px; border: 1px solid #d1d9e6;">
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="color: var(--primary-color);">Your Name <span class="required">*</span></label>
                    <input type="text" placeholder="Enter your full name">
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="color: var(--primary-color);">Your Email <span class="required">*</span></label>
                    <input type="email" placeholder="Enter your email address">
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="color: var(--primary-color);">Message <span class="required">*</span></label>
                    <textarea style="width: 100%; padding: 15px; border: 1.5px solid var(--primary-color); border-radius: 20px; outline: none; font-size: 0.85rem; resize: vertical; min-height: 120px;" placeholder="How can we help you?"></textarea>
                </div>
                <button class="btn-primary" style="width: 100%; border-radius: 20px; padding: 12px; font-size: 1rem; margin-top: 10px;">Send Message</button>
            </div>

        </div>
    </div>
    
    <!-- Panggil Footer di bawah halamannya -->
    <?php include __DIR__ . '/../page-customer/footer.php'; ?>
</div>