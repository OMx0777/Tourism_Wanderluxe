// This function triggers when the State dropdown changes
function fetchDistricts() {
    const stateId = document.getElementById('state').value;
    const districtSelect = document.getElementById('district');
    
    // Reset district dropdown
    districtSelect.innerHTML = '<option value="">Choose District</option>';
    
    if (stateId) {
        // Enable the district dropdown
        districtSelect.disabled = false;

        // In a real project, this calls your PHP backend using AJAX/Fetch:
        // fetch(`get_districts.php?state_id=${stateId}`)
        //     .then(response => response.json())
        //     .then(data => { ...populate dropdown... })
        
        // Mock data for immediate visual testing:
        const mockDistricts = {
            "1": ["Pune", "Mumbai", "Nagpur"],
            "2": ["Kochi", "Munnar", "Wayanad"],
            "3": ["Chennai", "Ooty", "Madurai"]
        };

        const districts = mockDistricts[stateId];
        districts.forEach(district => {
            let option = document.createElement('option');
            option.value = district.toLowerCase();
            option.textContent = district;
            districtSelect.appendChild(option);
        });
    } else {
        districtSelect.disabled = true;
    }
}