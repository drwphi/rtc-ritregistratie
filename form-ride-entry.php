<!-- form-ride-entry.php -->

<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>

<form id="rit-registratie" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>" method="post">
    <div>
        <label for="ride_date">Datum:</label>
        <input type="date" id="ride_date" name="ride_date" placeholder="dd-mm-jjjj" required>
    </div>
    <div>
        <label for="ride_type">Soort rit:</label>
        <select id="ride_type" name="ride_type" required>
            <option value="">Selecteer het soort rit</option>
            <option value="persoonlijk">Persoonlijk</option>
            <option value="rtc-veluwerijders">RTC (Veluwerijders)</option>
        </select>
    </div>
    <div>
        <label for="ride">Rit:</label>
        <input type="text" id="ride" name="ride" placeholder="Beschrijving van de rit">
    </div>
    <div>
        <label for="kilometers">Kilometers:</label>
        <input type="number" id="kilometers" name="kilometers" placeholder="Aantal kilometers" step="0.01" required>
    </div>
    <div style="display: flex;">
        <div style="margin-right: 10px;">
            <label for="duration_hours">Duur (Uren):</label>
            <input type="number" id="duration_hours" name="duration_hours" placeholder="Uren" min="0" max="23">
        </div>
        <div>
            <label for="duration_minutes">Duur (Minuten):</label>
            <input type="number" id="duration_minutes" name="duration_minutes" placeholder="Minuten" min="0" max="59">
        </div>
    </div>
    <div>
        <input type="submit" name="rtc_ritregistratie_submit" value="Rit Indienen">
    </div>
</form>

<div id="form-message"></div> <!-- Container for messages -->
