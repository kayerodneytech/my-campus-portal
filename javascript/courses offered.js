document.addEventListener('DOMContentLoaded', function() {
    const mainContentArea = document.getElementById('main-content-area');

    // ... (rest of your existing menu item handlers and other functions) ...

    // Courses Offered Link Handler (CORRECTED to show detailed courses and requirements)
    if (coursesOfferedLink && mainContentArea) {
        coursesOfferedLink.addEventListener('click', function(event) {
            event.preventDefault();
            setActiveMenuItem(this);
            const coursesContentHtml = `
                <h1>Courses Offered - August 2025 Intake</h1>
                <p>Explore our diverse range of programs designed to prepare you for success. Each course has specific entry requirements.</p>

                <div class="course-section">
                    <h2>General Entry Requirements</h2>
                    <ul>
                        <li><strong>Minimum Age:</strong> 18+ years.</li>
                        <li><strong>O'Levels:</strong> A minimum of 5 O'Levels including Mathematics, at least one language (e.g., English), and other relevant subjects specific to the course.</li>
                        <li><strong>A'Levels:</strong> Relevant A'Levels are an added advantage for higher-level courses.</li>
                    </ul>
                </div>

                <div class="course-section">
                    <h2>Course Levels & Preceding Qualifications</h2>
                    <ul>
                        <li><strong>Degree Programs:</strong>
                            <ul>
                                <li>2 years after obtaining a Higher National Diploma (HND) in a relevant field.</li>
                                <li>3 years after obtaining a National Diploma in a relevant field.</li>
                                <li>4 years after obtaining a National Certificate in a relevant field.</li>
                            </ul>
                        </li>
                        <li><strong>Higher National Diploma (HND):</strong>
                            <ul>
                                <li>2 years after obtaining a National Diploma in a relevant field.</li>
                                <li>3 years after obtaining a National Certificate in a relevant field.</li>
                            </ul>
                        </li>
                        <li><strong>National Diploma:</strong> Requires a preceding National Certificate in a relevant field.</li>
                        <li><strong>National Certificate (NC):</strong> Requires standard O'Level qualifications as specified below.</li>
                        <li><strong>National Foundation Certificate (NFC):</strong> Generally requires standard O'Level qualifications or demonstrated aptitude.</li>
                    </ul>
                </div>

                <div class="course-section">
                    <h2>Day Classes</h2>
                    <p>Refer to specific requirements outlined above and general requirements.</p>
                    <ul>
                        <li>ND In Information Technology</li>
                        <li>NC In Brick and Block Laying</li>
                        <li>NC In Wood Machining And Manufacturing Technology</li>
                        <li>NC In Information Technology (IT)</li>
                        <li>NC In Beauty Therapy</li>
                        <li>NC In Hairdressing</li>
                        <li>NC In Cosmetology</li>
                        <li>NFC In Brick and Block Laying</li>
                        <li>NFC In Plumbing and Drain Laying</li>
                        <li>NFC In Information Technology</li>
                        <li>NFC In Food Preparation</li>
                        <li>NFC In Tourism and Hospitality</li>
                    </ul>
                </div>

                <div class="course-section">
                    <h2>Day and Evening Classes</h2>
                    <p>Refer to specific requirements outlined above and general requirements.</p>
                    <ul>
                        <li>ND In Culinary Arts</li>
                        <li>ND In Tourism and Hospitality</li>
                        <li>NC In Machineshop Engineering (Fitting and Turning)</li>
                        <li>NC In Draughtsmanship Design Technology</li>
                        <li>NC In Carpentry and Joinery</li>
                        <li>NC In Tourism and Hospitality</li>
                        <li>NFC In Professional Cookery</li>
                        <li>NFC In Basic Machineshop Engineering</li>
                        <li>NFC In Car Maintenance</li>
                        <li>NFC In Welding Techniques</li>
                        <li>NFC In Carpentry</li>
                        <li>NFC In Cabinet Making</li>
                        <li>NFC In Joinery</li>
                    </ul>
                </div>

                <div class="course-section">
                    <h2>Evening Classes</h2>
                    <p>Refer to specific requirements outlined above and general requirements.</p>
                    <ul>
                        <li>NC In Diesel Plant Fitting</li>
                        <li>NC In Auto Electrics and Electronics</li>
                        <li>NC In Motor Vehicle Mechanics</li>
                        <li>NC In Accountancy</li>
                        <li>NC In Auto Electrics and Electronics</li>
                        <li>NFC In Electrical Installation</li>
                        <li>NFC In Diesel Plant Fitting</li>
                    </ul>
                </div>

                <div class="course-section">
                    <h2>Short Courses</h2>
                    <p>Typically require basic literacy and numeracy. Specific prerequisites may apply.</p>
                    <ul>
                        <li>Entrepreneurship</li>
                        <li>Welding Techniques</li>
                        <li>Lathe and Milling Work</li>
                        <li>Carpentry and Joinery</li>
                        <li>Brick and Block Laying</li>
                        <li>Plumbing and Drain Laying</li>
                        <li>Wheel Alignment</li>
                        <li>Hydraulics and Pneumatics</li>
                        <li>Solar Systems Installation</li>
                        <li>Tiling</li>
                        <li>Manicure and Pedicure</li>
                        <li>Facials</li>
                        <li>Braiding and Weaving</li>
                        <li>Closure Making, Wig Installation and Wig Making</li>
                        <li>Hairdressing</li>
                        <li>Massage Therapy</li>
                        <li>Cake Making and Decorating</li>
                        <li>Baking Techniques</li>
                        <li>Food Preparation</li>
                        <li>Basic Front Office Operations</li>
                        <li>Housekeeping</li>
                        <li>Food and Beverages Services</li>
                        <li>Interior Decor</li>
                        <li>African Attire</li>
                        <li>Global Uniform Projects</li>
                        <li>Work Wear Production</li>
                        <li>Children's Wear and Baby Wear</li>
                        <li>Curtain Decor</li>
                        <li>Upholstery</li>
                        <li>Fashion Design, Pattern Making and Sample Making</li>
                        <li>Dressmaking Bearing Men's and Ladies Executive Suits</li>
                        <li>Microsoft Packages (Word, Excel, Access, Powerpoint, Outlook)</li>
                        <li>Programming (C++, C#, Visual Basic, Python)</li>
                        <li>Graphic Design (Adobe InDesign, Adobe Photoshop, Adobe Illustrator)</li>
                        <li>Web Development (HTML, CSS, Javascript) - Front End/ Back End/ Databases /APIs</li>
                        <li>Computer Networking</li>
                    </ul>
                </div>

                <div class="course-section" style="margin-top: 30px; text-align: center;">
                    <h2>August 2025 Intake & Contact</h2>
                    <img src="../1000174122.jpg" alt="August 2025 Intake Courses" style="max-width: 100%; height: auto; display: block; margin: 20px auto;">
                    <p style="text-align: center; margin-top: 15px; font-size: 1.1em; font-weight: bold;">
                        ENROL TODAY!!!
                    </p>
                    <p style="text-align: center; font-size: 0.9em; color: #666;">
                        For more information on enrolment and courses contact us on:
                    </p>
                    <p style="text-align: center; font-size: 1em; font-weight: bold;">
                        📞 086 4428 3755 | 086 4428 3756 | 086 4428 3757
                    </p>
                    <p style="text-align: center; font-size: 0.9em; color: #666;">
                        📧 info@stpeterkubanaitc.ac.zw
                    </p>
                    <p style="text-align: center; font-size: 0.9em; color: #666;">
                        🌐 www.stpeterkubanaitc.ac.zw
                    </p>
                </div>
            `;
            mainContentArea.innerHTML = coursesContentHtml;
        });
    }

    // ... (rest of your script.js including initial click and loadForgotPasswordForm) ...

}); // End DOMContentLoaded
