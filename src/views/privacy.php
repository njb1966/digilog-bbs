<?php
/**
 * Privacy Policy Page
 */

// Start output buffering for content
ob_start();
?>

<h1 class="mb-lg">Privacy Policy</h1>

<div style="max-width: 800px; line-height: 1.8;">
    <p class="text-secondary mb-xl">
        <em>Last Updated: <?= date('F j, Y') ?></em>
    </p>

    <section style="margin-bottom: 2rem;">
        <h2 style="color: var(--accent); margin-bottom: 1rem;">1. Introduction</h2>
        <p style="margin-bottom: 1rem;">
            Welcome to Digilog ("we," "our," or "us"). We are committed to protecting your privacy and 
            ensuring transparency about how we collect, use, and safeguard your personal information. 
            This Privacy Policy explains our practices regarding data collected through our BBS-inspired 
            community platform.
        </p>
        <p>
            By using Digilog, you agree to the collection and use of information in accordance with this policy.
        </p>
    </section>

    <section style="margin-bottom: 2rem;">
        <h2 style="color: var(--accent); margin-bottom: 1rem;">2. Information We Collect</h2>
        
        <h3 style="color: var(--text-primary); margin: 1rem 0 0.5rem 0;">2.1 Account Information</h3>
        <p style="margin-bottom: 1rem;">When you register for an account, we collect:</p>
        <ul style="margin-left: 2rem; margin-bottom: 1rem;">
            <li><strong>Username:</strong> Your chosen display name</li>
            <li><strong>Email address:</strong> For account verification and communication</li>
            <li><strong>Password:</strong> Stored as a cryptographic hash (we never store plain-text passwords)</li>
            <li><strong>Registration date:</strong> Timestamp of account creation</li>
        </ul>

        <h3 style="color: var(--text-primary); margin: 1rem 0 0.5rem 0;">2.2 Content You Post</h3>
        <p style="margin-bottom: 1rem;">We store all content you voluntarily post on our platform:</p>
        <ul style="margin-left: 2rem; margin-bottom: 1rem;">
            <li>Message board posts and replies</li>
            <li>Message subjects and bodies</li>
            <li>Timestamps of all posts</li>
            <li>Thread relationships (replies to other messages)</li>
        </ul>

        <h3 style="color: var(--text-primary); margin: 1rem 0 0.5rem 0;">2.3 Usage Data</h3>
        <p style="margin-bottom: 1rem;">We automatically collect certain technical information:</p>
        <ul style="margin-left: 2rem; margin-bottom: 1rem;">
            <li><strong>IP Address:</strong> Logged with messages and contact form submissions for security and abuse prevention</li>
            <li><strong>Login activity:</strong> Last login timestamps</li>
            <li><strong>Reading activity:</strong> Read pointers to track which messages you've seen (for the "new since last visit" feature)</li>
            <li><strong>Session data:</strong> Temporary session cookies to keep you logged in</li>
        </ul>

        <h3 style="color: var(--text-primary); margin: 1rem 0 0.5rem 0;">2.4 Contact Form Submissions</h3>
        <p style="margin-bottom: 1rem;">When you use our contact form, we collect:</p>
        <ul style="margin-left: 2rem; margin-bottom: 1rem;">
            <li>Your name and email address</li>
            <li>Subject and message content</li>
            <li>IP address and submission timestamp</li>
        </ul>
    </section>

    <section style="margin-bottom: 2rem;">
        <h2 style="color: var(--accent); margin-bottom: 1rem;">3. How We Use Your Information</h2>
        <p style="margin-bottom: 1rem;">We use collected information for the following purposes:</p>
        <ul style="margin-left: 2rem; margin-bottom: 1rem;">
            <li><strong>Account Management:</strong> To create and maintain your user account</li>
            <li><strong>Core Functionality:</strong> To display your posts, track read status, and enable community features</li>
            <li><strong>Communication:</strong> To respond to your contact form inquiries via email</li>
            <li><strong>Security:</strong> To prevent abuse, spam, and unauthorized access</li>
            <li><strong>Technical Operation:</strong> To maintain session state and provide smooth user experience</li>
            <li><strong>Legal Compliance:</strong> To comply with applicable laws and respond to lawful requests</li>
        </ul>
    </section>

    <section style="margin-bottom: 2rem;">
        <h2 style="color: var(--accent); margin-bottom: 1rem;">4. Data Sharing and Disclosure</h2>
        <p style="margin-bottom: 1rem;">
            <strong>We do not sell, trade, or rent your personal information to third parties.</strong>
        </p>
        <p style="margin-bottom: 1rem;">We may share information only in the following circumstances:</p>
        <ul style="margin-left: 2rem; margin-bottom: 1rem;">
            <li><strong>Public Content:</strong> Your username and posts are publicly visible to all site visitors</li>
            <li><strong>Email Communication:</strong> We use ProtonMail SMTP to send contact form notifications and account-related emails</li>
            <li><strong>Legal Requirements:</strong> If required by law, court order, or governmental request</li>
            <li><strong>Security Threats:</strong> To protect against fraud, abuse, or security threats</li>
        </ul>
    </section>

    <section style="margin-bottom: 2rem;">
        <h2 style="color: var(--accent); margin-bottom: 1rem;">5. Cookies and Tracking</h2>
        <p style="margin-bottom: 1rem;">
            We use minimal cookies for essential functionality only:
        </p>
        <ul style="margin-left: 2rem; margin-bottom: 1rem;">
            <li><strong>Session Cookie:</strong> A temporary cookie to maintain your logged-in state (expires when you close your browser or after 24 hours)</li>
            <li><strong>CSRF Token:</strong> Security token to prevent cross-site request forgery attacks</li>
        </ul>
        <p style="margin-bottom: 1rem;">
            <strong>We do NOT use:</strong>
        </p>
        <ul style="margin-left: 2rem; margin-bottom: 1rem;">
            <li>Third-party analytics (no Google Analytics, etc.)</li>
            <li>Advertising cookies</li>
            <li>Social media tracking pixels</li>
            <li>Behavioral tracking or profiling</li>
        </ul>
    </section>

    <section style="margin-bottom: 2rem;">
        <h2 style="color: var(--accent); margin-bottom: 1rem;">6. Data Retention</h2>
        <p style="margin-bottom: 1rem;">We retain your information as follows:</p>
        <ul style="margin-left: 2rem; margin-bottom: 1rem;">
            <li><strong>Account Data:</strong> Retained as long as your account is active</li>
            <li><strong>Posts and Messages:</strong> Retained indefinitely unless deleted by you or a moderator</li>
            <li><strong>Deleted Messages:</strong> Soft-deleted messages are retained in our database for 90 days before permanent removal (unless manually purged by administrators)</li>
            <li><strong>Contact Form Messages:</strong> Retained for 1 year</li>
            <li><strong>IP Address Logs:</strong> Retained for 90 days</li>
            <li><strong>Session Data:</strong> Automatically expires after 24 hours</li>
        </ul>
    </section>

    <section style="margin-bottom: 2rem;">
        <h2 style="color: var(--accent); margin-bottom: 1rem;">7. Your Rights and Choices</h2>
        <p style="margin-bottom: 1rem;">You have the following rights regarding your personal data:</p>
        <ul style="margin-left: 2rem; margin-bottom: 1rem;">
            <li><strong>Access:</strong> You can view your account information and posts at any time</li>
            <li><strong>Correction:</strong> Contact us to update incorrect information</li>
            <li><strong>Deletion:</strong> You may request account deletion by contacting us (note that public posts may remain visible)</li>
            <li><strong>Data Export:</strong> Request a copy of your data by contacting us</li>
            <li><strong>Opt-Out:</strong> You can stop using the service at any time by deleting your account</li>
        </ul>
        <p style="margin-bottom: 1rem;">
            To exercise these rights, please contact us via the <a href="/?page=contact">contact form</a> or email 
            <a href="mailto:<?= e(ADMIN_EMAIL) ?>"><?= e(ADMIN_EMAIL) ?></a>.
        </p>
    </section>

    <section style="margin-bottom: 2rem;">
        <h2 style="color: var(--accent); margin-bottom: 1rem;">8. Security</h2>
        <p style="margin-bottom: 1rem;">
            We implement reasonable security measures to protect your information:
        </p>
        <ul style="margin-left: 2rem; margin-bottom: 1rem;">
            <li>Passwords are hashed using industry-standard bcrypt algorithm</li>
            <li>Database credentials are stored securely and not exposed to the public</li>
            <li>CSRF protection on all forms</li>
            <li>Regular security updates and monitoring</li>
            <li>IP-based abuse detection</li>
        </ul>
        <p style="margin-bottom: 1rem;">
            However, no method of transmission over the internet is 100% secure. While we strive to protect 
            your information, we cannot guarantee absolute security.
        </p>
    </section>

    <section style="margin-bottom: 2rem;">
        <h2 style="color: var(--accent); margin-bottom: 1rem;">9. Children's Privacy</h2>
        <p style="margin-bottom: 1rem;">
            Digilog is not intended for children under 13 years of age. We do not knowingly collect personal 
            information from children under 13. If you become aware that a child has provided us with personal 
            information, please contact us, and we will take steps to delete such information.
        </p>
    </section>

    <section style="margin-bottom: 2rem;">
        <h2 style="color: var(--accent); margin-bottom: 1rem;">10. International Users</h2>
        <p style="margin-bottom: 1rem;">
            Digilog is hosted in the United States. If you are accessing our service from outside the United States, 
            please be aware that your information may be transferred to, stored, and processed in the United States. 
            By using our service, you consent to this transfer.
        </p>
    </section>

    <section style="margin-bottom: 2rem;">
        <h2 style="color: var(--accent); margin-bottom: 1rem;">11. Changes to This Privacy Policy</h2>
        <p style="margin-bottom: 1rem;">
            We may update this Privacy Policy from time to time. We will notify users of significant changes by:
        </p>
        <ul style="margin-left: 2rem; margin-bottom: 1rem;">
            <li>Posting a notice on the site dashboard</li>
            <li>Updating the "Last Updated" date at the top of this policy</li>
        </ul>
        <p>
            Your continued use of Digilog after changes are posted constitutes acceptance of the updated policy.
        </p>
    </section>

    <section style="margin-bottom: 2rem;">
        <h2 style="color: var(--accent); margin-bottom: 1rem;">12. Contact Information</h2>
        <p style="margin-bottom: 1rem;">
            If you have questions, concerns, or requests regarding this Privacy Policy or our data practices, 
            please contact us:
        </p>
        <ul style="list-style: none; margin-left: 0; margin-bottom: 1rem;">
            <li><strong>Email:</strong> <a href="mailto:<?= e(ADMIN_EMAIL) ?>"><?= e(ADMIN_EMAIL) ?></a></li>
            <li><strong>Contact Form:</strong> <a href="/?page=contact">Digilog Contact Page</a></li>
        </ul>
    </section>

    <section style="margin-bottom: 2rem; padding: 1.5rem; background: var(--bg-secondary); border-left: 3px solid var(--accent); border-radius: var(--radius);">
        <h3 style="color: var(--accent); margin-bottom: 1rem;">Summary</h3>
        <p style="margin-bottom: 0.5rem;">
            <strong>What we collect:</strong> Account info, your posts, IP addresses, basic usage data
        </p>
        <p style="margin-bottom: 0.5rem;">
            <strong>What we don't do:</strong> Sell your data, use analytics trackers, show ads, or track you across the web
        </p>
        <p>
            <strong>Your control:</strong> You can delete your account and request data export at any time
        </p>
    </section>
</div>

<?php
$content = ob_get_clean();
$page_title = 'Privacy Policy';
$active_page = 'privacy';
require __DIR__ . '/layout.php';
?>
