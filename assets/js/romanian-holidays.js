/**
 * JavaScript pentru Sărbătorile Legale Românești
 * Expune funcțiile pentru frontend
 */

(function() {
    'use strict';
    
    // Cache pentru sărbători
    let holidaysCache = {};
    
    // Funcție pentru a verifica dacă o dată este sărbătoare
    function isHoliday(dateString) {
        const year = dateString.split('-')[0];
        
        // Verifică cache-ul
        if (holidaysCache[year]) {
            if (Array.isArray(holidaysCache[year])) {
                return holidaysCache[year].includes(dateString);
            } else if (holidaysCache[year].dates) {
                return holidaysCache[year].dates.includes(dateString);
            }
        }
        
        // Încarcă sărbătorile pentru anul respectiv
        loadHolidaysForYear(year);
        
        // Pentru moment, returnează false (va fi actualizat când se încarcă datele)
        return false;
    }
    
    // Funcție pentru a obține numele sărbătorii
    function getHolidayName(dateString) {
        const year = dateString.split('-')[0];
        
        if (holidaysCache[year]) {
            if (holidaysCache[year].names && holidaysCache[year].names[dateString]) {
                return holidaysCache[year].names[dateString];
            }
        }
        
        return 'Sărbătoare legală';
    }
    
    // Funcție pentru a încărca sărbătorile pentru un an
    function loadHolidaysForYear(year) {
        if (holidaysCache[year]) {
            return; // Deja încărcat
        }
        
        // Folosește AJAX pentru a obține sărbătorile
        if (typeof jQuery !== 'undefined' && jQuery.ajax) {
            jQuery.ajax({
                url: window.ajaxurl || '/wp-admin/admin-ajax.php',
                type: 'POST',
                data: {
                    action: 'clinica_get_romanian_holidays',
                    year: year,
                    nonce: window.clinica_ajax ? window.clinica_ajax.nonce : ''
                },
                success: function(response) {
                    if (response && response.success && response.data) {
                        // Dacă backend-ul returnează doar array-ul de date, convertim la format cu nume
                        if (Array.isArray(response.data)) {
                            holidaysCache[year] = {
                                dates: response.data,
                                names: {}
                            };
                        } else {
                            holidaysCache[year] = response.data;
                        }
                    }
                },
                error: function() {
                    // Fallback la sărbătorile hardcodate
                    holidaysCache[year] = getFallbackHolidays(year);
                }
            });
        } else {
            // Fallback la sărbătorile hardcodate
            holidaysCache[year] = getFallbackHolidays(year);
        }
    }
    
    // Funcție fallback cu sărbătorile hardcodate
    function getFallbackHolidays(year) {
        const holidays = {
            dates: [
                year + '-01-01', // Anul Nou
                year + '-01-02', // Anul Nou
                year + '-01-24', // Ziua Unirii Principatelor Române
                year + '-05-01', // Ziua Muncii
                year + '-06-01', // Ziua Copilului
                year + '-08-15', // Adormirea Maicii Domnului
                year + '-11-30', // Sfântul Andrei
                year + '-12-01', // Ziua Națională
                year + '-12-25', // Crăciunul
                year + '-12-26'  // Crăciunul
            ],
            names: {
                [year + '-01-01']: 'Anul Nou',
                [year + '-01-02']: 'Anul Nou',
                [year + '-01-24']: 'Ziua Unirii Principatelor Române',
                [year + '-05-01']: 'Ziua Muncii',
                [year + '-06-01']: 'Ziua Copilului',
                [year + '-08-15']: 'Adormirea Maicii Domnului',
                [year + '-11-30']: 'Sfântul Andrei',
                [year + '-12-01']: 'Ziua Națională',
                [year + '-12-25']: 'Crăciunul',
                [year + '-12-26']: 'Crăciunul'
            }
        };
        
        // Adaugă Paștele Ortodox (calcul simplu)
        const easter = calculateOrthodoxEaster(year);
        if (easter) {
            holidays.dates.push(easter);
            holidays.dates.push(getDateAfterDays(easter, 1)); // A doua zi de Paște
            holidays.dates.push(getDateAfterDays(easter, -2)); // Vinerea Mare
            holidays.dates.push(getDateAfterDays(easter, 49)); // Rusaliile
            holidays.dates.push(getDateAfterDays(easter, 50)); // A doua zi de Rusalii
            
            holidays.names[easter] = 'Paștele Ortodox';
            holidays.names[getDateAfterDays(easter, 1)] = 'A doua zi de Paște';
            holidays.names[getDateAfterDays(easter, -2)] = 'Vinerea Mare';
            holidays.names[getDateAfterDays(easter, 49)] = 'Rusaliile';
            holidays.names[getDateAfterDays(easter, 50)] = 'A doua zi de Rusalii';
        }
        
        return holidays;
    }
    
    // Calcul simplu pentru Paștele Ortodox
    function calculateOrthodoxEaster(year) {
        // Algoritm Gauss pentru Paștele Ortodox
        const a = year % 19;
        const b = year % 7;
        const c = year % 4;
        
        const d = (19 * a + 16) % 30;
        const e = (2 * c + 4 * b + 6 * d) % 7;
        
        let day = d + e + 3;
        let month = 3;
        
        if (day > 30) {
            day -= 30;
            month = 4;
        }
        
        return year + '-' + String(month).padStart(2, '0') + '-' + String(day).padStart(2, '0');
    }
    
    // Funcție helper pentru a adăuga zile la o dată
    function getDateAfterDays(dateString, days) {
        const date = new Date(dateString);
        date.setDate(date.getDate() + days);
        return date.toISOString().split('T')[0];
    }
    
    // Expune funcțiile global
    window.ClinicaRomanianHolidays = {
        isHoliday: isHoliday,
        getHolidayName: getHolidayName,
        loadHolidaysForYear: loadHolidaysForYear
    };
    
})();
