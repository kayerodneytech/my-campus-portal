// In script.js

// ... existing menu item handlers ...

// Student Organizations
const studentGovernmentLink = document.getElementById('student-government-link');
const academicClubsLink = document.getElementById('academic-clubs-link');
const culturalOrgsLink = document.getElementById('cultural-orgs-link');
const sportsRecreationLink = document.getElementById('sports-recreation-link');
const volunteerGroupsLink = document.getElementById('volunteer-groups-link');

if (studentGovernmentLink) {
    studentGovernmentLink.addEventListener('click', function(e) {
        e.preventDefault();
        loadContent('student_government', 'Student Government');
        setActiveMenuItem(this);
    });
}
if (academicClubsLink) {
    academicClubsLink.addEventListener('click', function(e) {
        e.preventDefault();
        loadContent('academic_clubs', 'Academic Clubs');
        setActiveMenuItem(this);
    });
}
if (culturalOrgsLink) {
    culturalOrgsLink.addEventListener('click', function(e) {
        e.preventDefault();
        loadContent('cultural_organizations', 'Cultural Organizations');
        setActiveMenuItem(this);
    });
}
if (sportsRecreationLink) {
    sportsRecreationLink.addEventListener('click', function(e) {
        e.preventDefault();
        loadContent('sports_recreation', 'Sports & Recreation');
        setActiveMenuItem(this);
    });
}
if (volunteerGroupsLink) {
    volunteerGroupsLink.addEventListener('click', function(e) {
        e.preventDefault();
        loadContent('volunteer_groups', 'Volunteer Groups');
        setActiveMenuItem(this);
    });
}

// ... rest of your script.js ...
