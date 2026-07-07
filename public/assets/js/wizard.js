document.addEventListener('DOMContentLoaded', () => {
    const steps = document.querySelectorAll('.wizard-steps-premium .step-indicator');
    const contents = document.querySelectorAll('.wizard-step-content');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');
    const form = document.getElementById('preInscriptionForm');
    const alertBox = document.getElementById('wizardAlert');
    
    let currentStep = 1;
    const totalSteps = 5;

    // Helper to show alert message
    function showAlert(msg) {
        alertBox.innerHTML = msg;
        alertBox.style.display = 'block';
        alertBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function hideAlert() {
        alertBox.style.display = 'none';
    }

    // Step Validation
    function validateStep(step) {
        hideAlert();
        
        if (step === 1) {
            const nin = document.getElementById('nin').value.trim();
            const prenomAr = form.elements['prenom_ar'].value.trim();
            const nomAr = form.elements['nom_ar'].value.trim();
            const nomFr = form.elements['nom_fr'].value.trim();
            const prenomFr = form.elements['prenom_fr'].value.trim();
            const dob = document.getElementById('date_naissance').value;
            const sexe = form.elements['sexe'].value;

            if (!nin || nin.length !== 18 || !/^\d+$/.test(nin)) {
                showAlert('رقم التعريف الوطني غير صحيح. يجب أن يتكون من 18 رقماً / Le NIN doit contenir exactement 18 chiffres.');
                return false;
            }

            // High-fidelity validation: Check if NIN contains the candidate birth year details (for example, digits 11-12 or similar depending on Algerian standard)
            if (dob) {
                const birthYearSuffix = dob.substring(2, 4);
                // In Algeria, NIN digits 2-3 represent the birth year suffix
                const ninYear = nin.substring(1, 3);
                if (ninYear !== birthYearSuffix) {
                    showAlert('رقم التعريف الوطني لا يتطابق مع سنة الميلاد المدخلة / Le NIN ne correspond pas à l\'année de naissance.');
                    return false;
                }
            }

            if (!prenomAr || !nomAr || !nomFr || !prenomFr || !dob || !sexe) {
                showAlert('يرجى ملء جميع الحقول الإلزامية قبل الانتقال للخطوة التالية / Veuillez remplir tous les champs obligatoires.');
                return false;
            }
        }

        if (step === 2) {
            const wilaya = form.elements['wilaya_residence_id'].value;
            const commune = form.elements['commune_residence_id'].value;
            const address = form.elements['adresse'].value.trim();
            const phone = form.elements['telephone'].value.trim();

            if (!wilaya || !commune || !address || !phone) {
                showAlert('يرجى ملء جميع معلومات العنوان والاتصال / Veuillez renseigner l\'adresse et le contact.');
                return false;
            }

            if (!/^(05|06|07)\d{8}$/.test(phone)) {
                showAlert('رقم الهاتف غير صحيح. يجب أن يبدأ بـ 05 أو 06 أو 07 ويتكون من 10 أرقام / N° de téléphone invalide.');
                return false;
            }
        }

        if (step === 3) {
            const niveau = form.elements['niveau_requis'].value;
            const mode = form.elements['mode_formation'].value;
            const etab = form.elements['etablissement_id'].value;
            const offre = form.elements['offre_id'].value;

            if (!niveau || !mode || !etab || !offre) {
                showAlert('يرجى تحديد اختيار التكوين والمؤسسة بدقة / Veuillez spécifier vos choix de formation.');
                return false;
            }
        }

        if (step === 4) {
            const photo = document.getElementById('doc_photo').files.length;
            const scolarite = document.getElementById('doc_scolarite').files.length;
            const medical = document.getElementById('doc_medical').files.length;

            if (photo === 0 || scolarite === 0 || medical === 0) {
                showAlert('يرجى تحميل جميع الوثائق المطلوبة بصيغ صحيحة / Veuillez uploader tous les documents requis.');
                return false;
            }
        }

        return true;
    }

    // Populate Review Step 5 dynamically
    function populateReview() {
        document.getElementById('rev_nin').innerText = form.elements['nin'].value;
        document.getElementById('rev_nom').innerText = 
            form.elements['prenom_ar'].value + ' ' + form.elements['nom_ar'].value + ' (' + 
            form.elements['prenom_fr'].value + ' ' + form.elements['nom_fr'].value + ')';
        
        document.getElementById('rev_date').innerText = form.elements['date_naissance'].value;
        document.getElementById('rev_sexe').innerText = form.elements['sexe'].value;
        
        const wilayaSelect = document.getElementById('wilaya_residence_id');
        const communeSelect = document.getElementById('commune_residence_id');
        document.getElementById('rev_commune').innerText = 
            communeSelect.options[communeSelect.selectedIndex].text + ' - ' + 
            wilayaSelect.options[wilayaSelect.selectedIndex].text;
        
        document.getElementById('rev_phone').innerText = form.elements['telephone'].value;

        const specSelect = document.getElementById('offre_id');
        const modeSelect = document.getElementById('mode_formation');
        document.getElementById('rev_specialite').innerText = specSelect.options[specSelect.selectedIndex].text;
        document.getElementById('rev_mode').innerText = modeSelect.options[modeSelect.selectedIndex].text;
    }

    function updateWizard() {
        contents.forEach(content => content.classList.remove('active'));
        document.getElementById(`step-${currentStep}`).classList.add('active');

        steps.forEach((indicator, index) => {
            indicator.classList.remove('active', 'completed');
            const stepNum = index + 1;
            if (stepNum === currentStep) {
                indicator.classList.add('active');
            } else if (stepNum < currentStep) {
                indicator.classList.add('completed');
            }
        });

        if (currentStep === 1) {
            prevBtn.style.display = 'none';
        } else {
            prevBtn.style.display = 'inline-block';
        }

        if (currentStep === totalSteps) {
            nextBtn.style.display = 'none';
            submitBtn.style.display = 'inline-block';
            populateReview();
        } else {
            nextBtn.style.display = 'inline-block';
            submitBtn.style.display = 'none';
        }
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            if (validateStep(currentStep)) {
                if (currentStep < totalSteps) {
                    currentStep++;
                    updateWizard();
                }
            }
        });
    }

    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            if (currentStep > 1) {
                currentStep--;
                updateWizard();
            }
        });
    }

    // Dynamic Commune selector
    const wilayaSelect = document.getElementById('wilaya_residence_id');
    const communeSelect = document.getElementById('commune_residence_id');
    if (wilayaSelect) {
        wilayaSelect.addEventListener('change', () => {
            communeSelect.innerHTML = '';
            if (wilayaSelect.value === '31') { // Oran
                communeSelect.innerHTML = `
                    <option value="1">السانية / Es-Senia</option>
                    <option value="2">بير الجير / Bir El Djir</option>
                    <option value="3">وهران / Oran</option>
                `;
            } else if (wilayaSelect.value === '16') { // Alger
                communeSelect.innerHTML = `
                    <option value="4">الجزائر الوسطى / Alger Centre</option>
                    <option value="5">باب الواد / Bab El Oued</option>
                    <option value="6">الحراش / El Harrach</option>
                `;
            } else {
                communeSelect.innerHTML = '<option value="9">بلدية افتراضية / Commune Demo</option>';
            }
        });
    }

    // File Drag & Drop visual feedback
    document.querySelectorAll('.file-drop-zone input').forEach(input => {
        input.addEventListener('change', (e) => {
            const file = e.target.files[0];
            const textZone = e.target.nextElementSibling;
            if (file) {
                textZone.innerHTML = `🟢 تم تحميل: ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`;
                textZone.style.color = 'var(--color-gov-green)';
            }
        });
    });

    // AJAX Form submit with registration confirmation card popup
    if (form) {
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            
            const check = document.getElementById('declaration_honneur').checked;
            if (!check) {
                showAlert('يجب الإقرار بشرفك بصحة المعلومات المدخلة للمواصلة / Vous devez cocher la déclaration sur l\'honneur.');
                return;
            }

            submitBtn.disabled = true;
            submitBtn.innerText = 'جاري الإرسال / Envoi...';

            const formData = new FormData(form);

            fetch('/sig/inscription', {
                method: 'POST',
                body: formData
            })
            .then(async res => {
                const data = await res.json();
                if (!res.ok || data.status !== 'success') {
                    throw new Error(data.message || 'Erreur serveur');
                }
                return data;
            })
            .then(data => {
                const regCode = data.numero_inscription || ('PI' + Math.floor(100000 + Math.random() * 900000));
                
                // Replace Form body with a gorgeous printable confirmation card!
                document.querySelector('.wizard-body-premium').innerHTML = `
                    <div class="registration-success-card text-center">
                        <div class="success-icon" style="font-size: 4.5rem; color: var(--color-gov-green); margin-bottom: 1.5rem;">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>
                        <h2 style="color: var(--color-gov-purple-dark); font-weight: 800; margin-bottom: 0.5rem;">تم التسجيل بنجاح! / Inscription Réussie !</h2>
                        <p class="text-muted" style="margin-bottom: 2rem;">لقد تم إرسال ملفك وتوليد استمارة التسجيل الأولي للمتربص بنجاح.</p>

                        <!-- Printable Card layout -->
                        <div class="official-card-receipt" style="border: 2px solid var(--color-gov-purple); border-radius: 12px; padding: 2rem; background: white; max-width: 650px; margin: 0 auto 2.5rem; text-align: right; position: relative;">
                            <div class="receipt-header" style="border-bottom: 2px solid var(--color-gov-purple); padding-bottom: 1rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
                                <div style="text-align: right;">
                                    <h4 style="margin:0; font-weight:700;">وزارة التكوين والتعليم المهنيين</h4>
                                    <small class="text-muted">Ministère de la Formation Professionnelle</small>
                                </div>
                                <img src="/sig/assets/images/logo.png" style="height: 45px;">
                            </div>
                            
                            <div class="receipt-body">
                                <table style="width:100%; border-collapse:collapse;">
                                    <tr style="border-bottom:1px solid #eee;">
                                        <th style="padding:0.8rem 0; width:40%;">رقم التسجيل الأولي / N° Inscription</th>
                                        <td style="color: var(--color-gov-purple); font-weight:800; font-size:1.2rem;" dir="ltr">${regCode}</td>
                                    </tr>
                                    <tr style="border-bottom:1px solid #eee;">
                                        <th style="padding:0.8rem 0;">الاسم واللقب / Nom & Prénom</th>
                                        <td style="font-weight:700;">${form.elements['prenom_ar'].value} ${form.elements['nom_ar'].value} / ${form.elements['prenom_fr'].value} ${form.elements['nom_fr'].value}</td>
                                    </tr>
                                    <tr style="border-bottom:1px solid #eee;">
                                        <th style="padding:0.8rem 0;">رقم التعريف الوطني / NIN</th>
                                        <td dir="ltr">${form.elements['nin'].value}</td>
                                    </tr>
                                    <tr style="border-bottom:1px solid #eee;">
                                        <th style="padding:0.8rem 0;">التخصص المطلوب / Spécialité</th>
                                        <td>${document.getElementById('offre_id').options[document.getElementById('offre_id').selectedIndex].text}</td>
                                    </tr>
                                    <tr>
                                        <th style="padding:0.8rem 0;">المؤسسة المستقبلة / Établissement</th>
                                        <td>${document.getElementById('etablissement_id').options[document.getElementById('etablissement_id').selectedIndex].text}</td>
                                    </tr>
                                </table>
                            </div>

                            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:2rem; border-top:1px solid #eee; padding-top:1.5rem;">
                                <div style="text-align:center; flex:1;">
                                    <div style="width: 80px; height: 80px; border: 1px solid #000; margin: 0 auto; padding: 5px;">
                                        <div style="width:100%; height:100%; background: repeating-conic-gradient(from 45deg, #000 0 25%, #fff 0 50%) 0 0/8px 8px;"></div>
                                    </div>
                                    <small class="text-muted" style="margin-top: 5px; display:block;">رمز التحقق / QR Code</small>
                                </div>
                                <div style="text-align:center; flex:1;">
                                    <strong>توقيع المترشح / Signature</strong>
                                    <div style="height:60px;"></div>
                                </div>
                            </div>
                        </div>

                        <div class="actions">
                            <button onclick="window.print();" class="btn btn-primary-premium">🖨️ طباعة وصل التسجيل / Imprimer le reçu</button>
                            <a href="/sig/" class="btn btn-secondary-premium" style="text-decoration:none; display:inline-block; margin-right:1rem;">العودة للرئيسية / Retour</a>
                        </div>
                    </div>
                `;
            })
            .catch(err => {
                showAlert('حدث خطأ أثناء الاتصال بالخادم. يرجى المحاولة لاحقاً / Une erreur est survenue.');
                submitBtn.disabled = false;
                submitBtn.innerText = 'تأكيد وإرسال الملف / Confirmer';
            });
        });
    }
});
