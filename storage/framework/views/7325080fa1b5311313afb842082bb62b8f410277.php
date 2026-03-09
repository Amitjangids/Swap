<section class="banner-section password-section">

    <!-- Load SDK -->
    <!-- <script src="https://cdn.smileidentity.com/js/v1/smile-identity.min.js"></script> -->
    <script src="https://cdn.smileidentity.com/inline/v10/js/script.min.js"></script>

    <div id="smile-container"></div>

    <script>
        async function startSmileId() {

            // Wait until SDK is loaded
            if (typeof window.SmileIdentityWeb === "undefined") {
                console.error("SmileIdentityWeb SDK not loaded");
                return;
            }

            const sigRes = await fetch('https://internal.swap-africa.net/smileid/signature');
            const sigData = await sigRes.json();

            const smileIdentity = new window.SmileIdentityWeb({
                partnerId: sigData.partner_id,
                signature: sigData.signature,
                timestamp: sigData.timestamp,
                environment: "sandbox" // or "sandbox"
            });

            console.log("Smile ID initialized", smileIdentity);

            smileIdentity.startSmartSelfie({
                containerId: "smile-container",
                onSuccess: (result) => {
                    console.log("Capture success", result);
                },
                onError: (error) => {
                    console.error("Smile ID Error", error);
                }
            });
        }

        // Start AFTER page load
        window.onload = startSmileId;
    </script>

</section>
<?php /**PATH /var/www/internal-swap-africa/resources/views/dashboard/capture-face.blade.php ENDPATH**/ ?>