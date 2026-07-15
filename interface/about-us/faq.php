<style>
    .faq-container { display: flex; flex-direction: column; gap: 1rem; }
    .faq-item { background: white; border: 1.5px solid var(--divider-color); border-radius: 15px; padding: 1.25rem; transition: 0.3s ease; }
    .faq-item:hover { border-color: var(--primary-color); box-shadow: 0 5px 15px rgba(15, 56, 145, 0.05); }
    .faq-summary { font-weight: 600; color: var(--primary-color); cursor: pointer; list-style: none; font-size: 1.1rem; outline: none; display: flex; justify-content: space-between; align-items: center; }
    .faq-summary::-webkit-details-marker { display: none; }
    .faq-summary::after { content: '+'; font-size: 1.5rem; color: var(--primary-color); transition: 0.3s; }
    details[open] .faq-summary::after { content: '-'; transform: rotate(180deg); }
    .faq-content { margin-top: 1rem; color: var(--paragraf); line-height: 1.6; font-size: 0.95rem; border-top: 1px solid var(--divider-color); padding-top: 1rem; }
</style>

<div style="min-height: 100vh; display: flex; flex-direction: column; justify-content: space-between; background-color: var(--bg-light);">
    <div style="padding: 8rem 4rem 4rem 4rem; display: flex; justify-content: center; flex: 1;">
        <div style="max-width: 800px; width: 100%;">
            <h1 style="color: var(--primary-color); font-size: 3rem; margin-bottom: 1rem; text-align: center;" class="coolvetica">Frequently Asked Questions</h1>
            <p style="text-align: center; color: var(--paragraf); margin-bottom: 3rem; font-size: 1rem;">Find answers to common questions about CardHaven services below.</p>
            
            <div class="faq-container">
                <details class="faq-item">
                    <summary class="faq-summary">How long does shipping take?</summary>
                    <div class="faq-content">Orders are typically processed within 1-2 business days. Shipping usually takes 3-5 business days depending on your location. Pre-order items will be shipped based on their release date.</div>
                </details>
                
                <details class="faq-item">
                    <summary class="faq-summary">How does the Card Buyback system work?</summary>
                    <div class="faq-content">You can submit the cards you wish to sell through our buyback page. Our team will review the condition and rarity of the card, then provide a price offer. If you agree, you can send the card to us to receive your payment.</div>
                </details>

                <details class="faq-item">
                    <summary class="faq-summary">Are all cards sold here authentic?</summary>
                    <div class="faq-content">Yes! We guarantee that 100% of the cards sold at CardHaven, whether sealed products or single cards, are authentic and verified by our experts.</div>
                </details>
            </div>
        </div>
    </div>
    
    <?php include __DIR__ . '/../page-customer/footer.php'; ?>
</div>