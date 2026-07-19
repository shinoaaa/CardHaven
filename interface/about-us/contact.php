<div style="min-height: 100vh; display: flex; flex-direction: column; justify-content: space-between; background-color: var(--bg-light);">
    <div style="padding: 8rem 4rem 4rem 4rem; display: flex; justify-content: center; flex: 1;">
        <div class="content-card" style="width: 100%; max-width: 1000px; display: flex; flex-direction: row; gap: 4rem; min-height: auto; padding: 3rem;">
            
            <div style="flex: 1; display: flex; flex-direction: column; justify-content: center;">
                <h1 style="color: var(--primary-color); font-size: 3rem; margin-bottom: 1rem;" class="coolvetica">Contact Us</h1>
                <p style="color: var(--paragraf); line-height: 1.8; font-size: 1.1rem; margin-bottom: 2rem;">
                    Have questions, feedback, or need assistance with your cards? Drop us a message, and the CardHaven team will get back to you as soon as possible!
                </p>
                <div style="display: flex; flex-direction: column; gap: 1rem; color: var(--paragraf); font-size: 0.95rem;">
                    <p><strong style="color: var(--primary-color);">Email:</strong> cardhavensupport@gmail.com</p>
                    <p><strong style="color: var(--primary-color);">Phone:</strong> +62 812 3456 7890</p>
                    <p><strong style="color: var(--primary-color);">Address:</strong> CardHaven Main Store, Jakarta, Indonesia</p>
                </div>
            </div>

            <div style="flex: 1; background: #f8faff; padding: 2rem; border-radius: 20px; border: 1px solid #d1d9e6;">
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="color: var(--primary-color);">Your Name <span class="required">*</span></label>
                    <input type="text" id="contactName" placeholder="Enter your full name">
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="color: var(--primary-color);">Your Email <span class="required">*</span></label>
                    <input type="email" id="contactEmail" placeholder="Enter your email address">
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="color: var(--primary-color);">Message <span class="required">*</span></label>
                    <textarea id="contactMessage" style="width: 100%; padding: 15px; border: 1.5px solid var(--primary-color); border-radius: 20px; outline: none; font-size: 0.85rem; resize: vertical; min-height: 120px;" placeholder="How can we help you?"></textarea>
                </div>
                <!-- Honeypot: disembunyikan dari user; hanya bot yang mengisi.
                     Jangan pakai display:none saja (sebagian bot skip itu) —
                     digeser keluar layar + aria-hidden + tabindex -1. -->
                <div aria-hidden="true" style="position:absolute; left:-9999px; top:-9999px; width:1px; height:1px; overflow:hidden;">
                    <label>Company Website</label>
                    <input type="text" id="contactWebsite" name="website" tabindex="-1" autocomplete="off">
                </div>

                <button id="contactSendBtn" class="btn-primary" onclick="contactSendMessage()" style="width: 100%; border-radius: 20px; padding: 12px; font-size: 1rem; margin-top: 10px;">Send Message</button>
            </div>

        </div>
    </div>
    
    <!-- Panggil Footer di bawah halamannya -->
    <?php include __DIR__ . '/../page-customer/footer.php'; ?>
</div>

<script>
// Kirim pesan contact-us via server (PHPMailer + SMTP Gmail).
// Kalau SMTP belum dikonfigurasi di .env, fallback ke mailto: (buka aplikasi
// email user dengan pesan sudah terisi) supaya fiturnya tetap jalan.
const CONTACT_SUPPORT_EMAIL = 'cardhavensupport@gmail.com';
const CONTACT_API = '/cardhaven/interface/about-us/contact_controller.php';

function contactBuildMailto(name, email, message) {
    const subject = '[CardHaven] Message from ' + name;
    const body = 'Name: ' + name + '\nEmail: ' + email + '\n\n' + message;
    return 'mailto:' + CONTACT_SUPPORT_EMAIL
        + '?subject=' + encodeURIComponent(subject)
        + '&body=' + encodeURIComponent(body);
}

async function contactSendMessage() {
    const name    = (document.getElementById('contactName')?.value || '').trim();
    const email   = (document.getElementById('contactEmail')?.value || '').trim();
    const message = (document.getElementById('contactMessage')?.value || '').trim();
    const website = (document.getElementById('contactWebsite')?.value || '').trim(); // honeypot

    const toast = (icon, msg) => {
        if (typeof cardhavenToast === 'function') cardhavenToast(icon, msg);
        else alert(msg);
    };

    if (!name)    { toast('error', 'Please enter your name.'); return; }
    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { toast('error', 'Please enter a valid email address.'); return; }
    if (!message) { toast('error', 'Please write your message.'); return; }

    const btn = document.getElementById('contactSendBtn');
    if (btn) { btn.disabled = true; btn.textContent = 'Sending…'; }

    try {
        const res  = await fetch(CONTACT_API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name, email, message, website })
        });
        const data = await res.json();

        if (data.status === 'success') {
            toast('success', data.message || 'Your message has been sent!');
            document.getElementById('contactName').value = '';
            document.getElementById('contactEmail').value = '';
            document.getElementById('contactMessage').value = '';
        } else if (data.status === 'unconfigured') {
            // SMTP belum di-set → pakai jalur mailto
            window.location.href = contactBuildMailto(name, email, message);
            toast('success', 'Opening your email app…');
        } else {
            toast('error', data.message || 'Failed to send the message.');
        }
    } catch (e) {
        // Server tidak bisa dihubungi → tetap kasih jalan lewat mailto
        window.location.href = contactBuildMailto(name, email, message);
        toast('success', 'Opening your email app…');
    } finally {
        if (btn) { btn.disabled = false; btn.textContent = 'Send Message'; }
    }
}
</script>