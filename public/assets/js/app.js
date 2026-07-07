// Service Worker is registered dynamically in layout Blade templates to handle various subfolder scopes correctly.

// Global UI Premium Enhancements
document.addEventListener('DOMContentLoaded', () => {
    console.log('SGFEP App Premium Initialized');

    // 1. Dynamic Counter Animation for Statistics Grid
    const statNumbers = document.querySelectorAll('.stat-number');
    statNumbers.forEach(elem => {
        const text = elem.innerText.trim();
        // Remove spaces inside numbers (e.g. "1 817" -> 1817)
        const targetValue = parseInt(text.replace(/\s/g, ''));
        
        if (!isNaN(targetValue) && targetValue > 0) {
            let startValue = 0;
            const duration = 1500; // 1.5 seconds
            const stepTime = Math.abs(Math.floor(duration / targetValue));
            const step = Math.ceil(targetValue / 60); // 60fps increments

            const timer = setInterval(() => {
                startValue += step;
                if (startValue >= targetValue) {
                    startValue = targetValue;
                    clearInterval(timer);
                }
                // Format with spaces
                elem.innerText = startValue.toLocaleString('fr-FR');
            }, 20);
        }
    });

    // 2. Interactive Image Slider logic with background images
    const slides = [
        {
            img: "https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?q=80&w=2070&auto=format&fit=crop",
            title: "الخدمة العمومية / Service Public",
            text: "يتيح هذا الفضاء خدمات عمومية موجهة للمتربصين، المكونين، المستخدمين، الموظفين و كل الشركاء"
        },
        {
            img: "https://images.unsplash.com/photo-1581092160607-ee22621dd758?q=80&w=2070&auto=format&fit=crop",
            title: "فضاء التمهين والشركاء / Espace Apprentissage",
            text: "علاقات وطيدة بين المؤسسات التكوينية والشركاء الاقتصاديين لضمان إدماج مهني ناجح للمتخرجين"
        },
        {
            img: "https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?q=80&w=2070&auto=format&fit=crop",
            title: "الهندسة البيداغوجية والبرامج / Ingénierie Pédagogique",
            text: "تحديث مستمر لمدونة الشعب المهنية وتطوير مناهج التدريس بما يتماشى مع التطور التكنولوجي"
        }
    ];

    const sliderBg = document.querySelector('.slider-bg');
    const dots = document.querySelectorAll('.slider-dots .dot');
    const sliderTitle = document.querySelector('.slider-floating-card h3');
    const sliderText = document.querySelector('.slider-floating-card p');

    let currentSlide = 0;

    function switchSlide(index) {
        if (!sliderBg) return;
        
        currentSlide = index;

        // Apply smooth transition opacity
        sliderBg.style.opacity = 0.4;
        
        setTimeout(() => {
            sliderBg.style.backgroundImage = `url('${slides[currentSlide].img}')`;
            sliderTitle.innerHTML = slides[currentSlide].title;
            sliderText.innerHTML = slides[currentSlide].text;
            sliderBg.style.opacity = 1;
        }, 300);

        // Update dots state
        dots.forEach(d => d.classList.remove('active'));
        if (dots[currentSlide]) {
            dots[currentSlide].classList.add('active');
        }
    }

    if (dots.length > 0) {
        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                switchSlide(index);
            });
        });

        // Auto slide change every 6 seconds
        setInterval(() => {
            let next = (currentSlide + 1) % slides.length;
            switchSlide(next);
        }, 6000);
    }

    // ── Note: Bootstrap 5 handles all modal/dropdown interactions natively.
    // ── Global modal backdrop cleanup is registered in layouts/main.php.
    // ── Do NOT add custom modal fallbacks here — they create duplicate backdrops.
});

// Global Excel/CSV Exporter functions
function exportTableToCSV(tableId, filename = 'export.csv') {
    const table = document.getElementById(tableId);
    if (!table) return;
    let csv = [];
    const rows = table.querySelectorAll("tr");
    for (let i = 0; i < rows.length; i++) {
        if (rows[i].classList.contains('no-export')) continue;
        let row = [];
        const cols = rows[i].querySelectorAll("td, th");
        for (let j = 0; j < cols.length; j++) {
            if (cols[j].classList.contains('no-export')) continue;
            let text = cols[j].innerText.trim().replace(/"/g, '""');
            row.push('"' + text + '"');
        }
        csv.push(row.join(","));
    }
    const csvContent = "\uFEFF" + csv.join("\n");
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.setAttribute("href", url);
    link.setAttribute("download", filename);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function exportTableToExcel(tableId, filename = 'export.xls') {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    let html = table.outerHTML;
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = html;
    const tempTable = tempDiv.querySelector('table');
    
    tempTable.querySelectorAll('.no-export').forEach(el => el.remove());
    const tableHtml = tempTable.outerHTML;
    
    const excelTemplate = `
      <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
      <head>
        <meta http-equiv="content-type" content="application/vnd.ms-excel; charset=UTF-8">
        <!--[if gte mso 9]>
        <xml>
          <x:ExcelWorkbook>
            <x:ExcelWorksheets>
              <x:ExcelWorksheet>
                <x:Name>Sheet1</x:Name>
                <x:WorksheetOptions>
                  <x:DisplayRightToLeft/>
                </x:WorksheetOptions>
              </x:ExcelWorksheet>
            </x:ExcelWorksheets>
          </x:ExcelWorkbook>
        </xml>
        <![endif]-->
        <style>
          table { border-collapse: collapse; width: 100%; direction: rtl; }
          th { background-color: #482b8f; color: white; font-weight: bold; border: 1px solid #ddd; padding: 8px; text-align: right; }
          td { border: 1px solid #ddd; padding: 8px; text-align: right; }
        </style>
      </head>
      <body>
        ${tableHtml}
      </body>
      </html>
    `;
    
    const blob = new Blob([excelTemplate], { type: 'application/vnd.ms-excel;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.setAttribute("href", url);
    link.setAttribute("download", filename);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
