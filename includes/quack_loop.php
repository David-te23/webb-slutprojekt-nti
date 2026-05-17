<?php
// Fallback för administratörsstatus om variabeln inte redan skickats med från huvudvyerna
if (!isset($isAdmin)) {
    $isAdmin = (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1);
}
?>
 
 <?php if (empty($quacks)): ?>
    <!-- Tomt flöde: om det inte finns några inlägg att visa -->
    <div class="text-center p-5 bg-white rounded shadow-sm mt-3">
        <div class="fs-1 mb-3">🐥</div>
        <h4 class="fw-bold">It's quiet in here...</h4>
        <p class="text-muted">
            <?php
            //Dynamiskt felmeddelande baserat på om användaren kollar i following eller all quacks
             if (isset($_GET['filter']) && $_GET['filter'] === 'following'): ?>
                You aren't following anyone yet. Follow some people to see their quacks here!
                <?php else: ?>
                    No quacks found. Be the first one to quack!
                <?php endif; ?>
        </p>
    </div>
    <?php else: ?>
       <!-- INLÄGGS-LOOP: Itererar igenom alla hämtade quacks och inkluderar den gemensamma komponenten -->
        <?php foreach ($quacks as $quack) :
        include __DIR__ . '/quack_item.php'; ?>   
        <?php endforeach; ?>
    <?php endif; ?>