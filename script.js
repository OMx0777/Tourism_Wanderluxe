// script.js
async function fetchDistricts() {
    const stateId = document.getElementById('state').value;
    const districtSelect = document.getElementById('district');
    
    // Reset the district dropdown to default
    districtSelect.innerHTML = '<option value="">Choose District</option>';
    
    // If the user actually selected a state (not the default empty option)
    if (stateId) {
        try {
            // Fetch data from our PHP API
            const response = await fetch(`api_districts.php?state_id=${stateId}`);
            const districts = await response.json();
            
            // If we found districts, enable the dropdown and populate it
            if (districts.length > 0) {
                districtSelect.disabled = false;
                
                districts.forEach(district => {
                    let option = document.createElement('option');
                    option.value = district.id; // The database ID
                    option.textContent = district.name; // The visual name (e.g., Pune)
                    districtSelect.appendChild(option);
                });
            } else {
                // If no districts exist for this state yet
                districtSelect.disabled = true;
            }
            
        } catch (error) {
            console.error("Error fetching districts:", error);
            districtSelect.disabled = true;
        }
    } else {
        // If they switch back to "Select State", disable the district dropdown
        districtSelect.disabled = true;
    }
}