<?php
/**
 * Footer Component
 * Displays contact information and footer links
 * Used across all pages
 */

// Sample contact data - in a real application, this would come from a database
$contact_info = [
    'admissions' => [
        'title' => 'Admissions Office',
        'phone' => '0782 573 049',
        'email' => 'admissions@mycamp.edu',
        'hours' => 'Mon-Fri 8AM-5PM'
    ],
    'registrar' => [
        'title' => 'Registrar',
        'phone' => '(555) 123-4568',
        'email' => 'PollyBusiness@mycamp.edu',
        'hours' => 'Mon-Fri 9AM-4PM'
    ],
    'support' => [
        'title' => 'Student Support',
        'phone' => '(555) 123-4569',
        'email' => 'support@mycamp.edu',
        'hours' => '24/7 Online Chat'
    ],
    'location' => [
        'title' => 'Campus Location',
        'address' => '123 University Drive<br>Education City, EC 12345',
        'link' => 'View Campus Map'
    ]
];
?>

<footer class="bg-white rounded-2xl shadow-lg p-8 border border-gray-100">
    <h3 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
        <span class="text-3xl mr-3">📞</span>
        Contact Information
    </h3>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <?php foreach ($contact_info as $contact): ?>
        <div class="bg-gray-50 rounded-lg p-4">
            <h4 class="font-semibold text-gray-800 mb-3"><?php echo htmlspecialchars($contact['title']); ?></h4>
            <div class="text-sm text-gray-600 space-y-1">
                <?php if (isset($contact['phone'])): ?>
                    <div><strong>Phone:</strong> <?php echo htmlspecialchars($contact['phone']); ?></div>
                <?php endif; ?>
                <?php if (isset($contact['email'])): ?>
                    <div><strong>Email:</strong> <?php echo htmlspecialchars($contact['email']); ?></div>
                <?php endif; ?>
                <?php if (isset($contact['hours'])): ?>
                    <div><strong>Hours:</strong> <?php echo htmlspecialchars($contact['hours']); ?></div>
                <?php endif; ?>
                <?php if (isset($contact['address'])): ?>
                    <div><?php echo $contact['address']; ?></div>
                <?php endif; ?>
                <?php if (isset($contact['link'])): ?>
                    <div>
                        <a href="#" class="text-blue-600 hover:text-blue-800 transition-colors duration-200">
                            <?php echo htmlspecialchars($contact['link']); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <div class="border-t border-gray-200 pt-6 text-center text-gray-600">
        <p>
            © 2025 MyCamp Portal. All rights reserved. |
            <a href="#" class="text-blue-600 hover:text-blue-800 transition-colors duration-200">Privacy Policy</a> |
            <a href="#" class="text-blue-600 hover:text-blue-800 transition-colors duration-200">Terms of Service</a>
        </p>
    </div>
</footer>
