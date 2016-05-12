<h2>Contatos</h2>

<?php if ( $this->objectList != null ): ?>
<div class="contact-container">
<?php foreach ( $this->objectList as $contact ) : ?>
    <div class="contact-item">
        <div class="contact-name"><span class="label">Nome&nbsp;</span><?= $contact->name; ?></div>
        <div class="contact-email"><span class="label">E-mail&nbsp;</span><?= $contact->email; ?></div>
        <div class="contact-phone"><span class="label">Fone&nbsp;</span><?= $contact->phone; ?></div>
        <div class="contact-msg">
            <span class="label">Mensagem&nbsp;</span>
            <div class="contact-msg-text"><?= $contact->message; ?></div>
        </div>
    </div>
<?php endforeach; ?>
</div>
<?php else: ?>
    <p class="msg-notice">Não há contatos cadastrados.</p>
<?php endif; ?>
