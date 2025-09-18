# Fix Hidden Inputs - Actualizare

## Problema Identificată

Mesajele "Cleared hidden input" apăreau la încărcarea paginii pentru că funcția `syncHiddenInputs()` se executa și găsea input-urile vizibile goale (pentru că sunt în `div.cell-edit` cu `display: none`), apoi ștergea hidden input-urile corespunzătoare.

## Soluția Implementată

### 1. Funcție Separată pentru Încărcarea Paginii

Am creat o funcție separată `syncHiddenInputsOnLoad()` care:
- Nu șterge hidden input-urile dacă input-urile vizibile sunt goale la încărcare
- Actualizează hidden input-urile doar dacă input-urile vizibile au valori
- Păstrează valorile existente în hidden input-uri

### 2. Modificări în Cod

**Fișier:** `admin/views/settings.php`

**Funcția nouă adăugată:**
```javascript
// Sincronizează valorile cu hidden inputs la încărcare (fără să ștergi valorile existente)
function syncHiddenInputsOnLoad() {
    $('.time-cell input[type="time"]').each(function() {
        var input = $(this);
        var day = input.closest('.time-cell').data('day');
        var type = input.attr('name').includes('start') ? 'start' : 'end';
        var value = input.val();
        
        // Debug: Log the sync process
        console.log('Initial sync for day:', day, 'type:', type, 'value:', value);
        
        // Verifică dacă input-ul este valid și are o valoare
        if (value && value.trim() !== '') {
            // Actualizează hidden input
            var hiddenInput = $('input[name="working_hours[' + day + '][' + type + ']"]');
            hiddenInput.val(value);
            console.log('Updated hidden input on load:', hiddenInput[0], 'with value:', value);
        }
        // Nu șterge hidden input-ul dacă input-ul vizibil este gol la încărcare
    });
    
    // Sincronizează status-ul
    $('.status-cell input[type="checkbox"]').each(function() {
        var checkbox = $(this);
        var day = checkbox.closest('.status-cell').data('day');
        var isActive = checkbox.is(':checked');
        
        var hiddenInput = $('input[name="working_hours[' + day + '][active]"]');
        hiddenInput.val(isActive ? '1' : '0');
        console.log('Updated status hidden input on load for day:', day, 'active:', isActive);
    });
}
```

**Modificarea apelului din `$(document).ready()`:**
```javascript
// Inițializează sincronizarea (folosește versiunea care nu șterge valorile existente)
syncHiddenInputsOnLoad();
```

### 3. Diferențe între Funcții

| Funcție | Comportament |
|---------|-------------|
| `syncHiddenInputs()` | Șterge hidden input-ul dacă input-ul vizibil este gol |
| `syncHiddenInputsOnLoad()` | Nu șterge hidden input-ul dacă input-ul vizibil este gol la încărcare |

## Testare

### Fișier de Test Creat
`tools/debug/test-hidden-inputs-fix.php`

Acest fișier simulează comportamentul din settings.php și permite testarea:
- Funcției `syncHiddenInputs()` (originală)
- Funcției `syncHiddenInputsOnLoad()` (nouă)
- Comportamentului la încărcarea paginii
- Comportamentului la submit-ul formularului

### Pași de Testare

1. Deschide `tools/debug/test-hidden-inputs-fix.php` în browser
2. Verifică log-urile la încărcarea paginii
3. Apasă butonul "Test syncHiddenInputs()" - ar trebui să vezi mesaje "Cleared hidden input"
4. Apasă butonul "Test syncHiddenInputsOnLoad()" - nu ar trebui să vezi mesaje "Cleared hidden input"
5. Verifică că hidden input-urile păstrează valorile lor la încărcare

## Rezultate Așteptate

### Înainte de Fix
```
Syncing input for day: wednesday type: start value: 
Cleared hidden input for day: wednesday type: start
```

### După Fix
```
Initial sync for day: wednesday type: start value: 
// Nu mai apare "Cleared hidden input"
```

## Impact

- **Pozitiv:** Hidden input-urile nu mai sunt șterse la încărcarea paginii
- **Pozitiv:** Valorile salvate anterior sunt păstrate
- **Pozitiv:** Comportamentul la editare rămâne neschimbat
- **Neutru:** Funcția `syncHiddenInputs()` originală rămâne pentru evenimentele de editare

## Compatibilitate

- ✅ Compatibil cu funcționalitatea existentă
- ✅ Nu afectează editarea interactivă
- ✅ Păstrează toate evenimentele JavaScript existente
- ✅ Nu modifică structura HTML

## Verificare

Pentru a verifica că fix-ul funcționează:

1. Deschide pagina de setări în WordPress
2. Verifică consola browser-ului
3. Nu ar trebui să vezi mesaje "Cleared hidden input" la încărcarea paginii
4. Hidden input-urile ar trebui să păstreze valorile lor inițiale
5. Editarea și salvarea ar trebui să funcționeze normal

## Note Tehnice

- Problema era cauzată de faptul că input-urile vizibile sunt în `div.cell-edit` cu `display: none`
- La încărcarea paginii, aceste input-uri pot returna valori goale sau undefined
- Funcția originală `syncHiddenInputs()` trata aceste valori goale ca fiind intenționate de utilizator
- Noua funcție `syncHiddenInputsOnLoad()` tratează aceste valori goale ca fiind normale la încărcare 