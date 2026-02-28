<?php
/**
 * Email Footer Component - ECHO2026 Branding
 * Used across all email templates
 */
?>
<div class="email-footer">
    <p><strong>ECHO2026</strong> - Respond to the Call</p>
    <p>Questions? Contact us at <a href="mailto:<?php echo e(env('SMTP_FROM_EMAIL')); ?>"><?php echo e(env('SMTP_FROM_EMAIL')); ?></a></p>
    <p style="margin-top: 20px; font-size: 12px; color: #6b7280;">
        This is an automated email. Please keep this for your records.<br>
        Sizewell Hall, Sizewell, Leiston, Suffolk, IP16 4TX
    </p>
</div>
