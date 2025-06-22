/**
 * AdStart AdZone JavaScript
 * Standalone ad loading script
 */
(function() {
    'use strict';
    
    // Get script parameters from current script tag
    const scripts = document.getElementsByTagName('script');
    const currentScript = scripts[scripts.length - 1];
    const src = currentScript.src;
    
    if (!src) {
        console.error('AdZone: Could not determine script source');
        return;
    }
    
    // Parse parameters from script URL
    const urlParts = src.split('?');
    if (urlParts.length < 2) {
        console.error('AdZone: No parameters found in script URL');
        return;
    }
    
    const params = new URLSearchParams(urlParts[1]);
    const zoneToken = params.get('zone');
    const size = params.get('size') || '300x250';
    const domain = params.get('domain') || 'https://up.adstart.click';
    
    if (!zoneToken) {
        console.error('AdZone: Missing zone parameter');
        return;
    }
    
    // Create container if it doesn't exist
    let container = document.getElementById('adzone-' + zoneToken);
    if (!container) {
        container = document.createElement('div');
        container.id = 'adzone-' + zoneToken;
        currentScript.parentNode.insertBefore(container, currentScript);
    }
    
    // Set container styles
    const [width, height] = size.split('x');
    container.style.cssText = `
        width: ${width}px;
        height: ${height}px;
        border: 1px solid #ddd;
        background: #f5f5f5;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #666;
        font-family: Arial, sans-serif;
        font-size: 14px;
        overflow: hidden;
        position: relative;
    `;
    
    // Set loading state
    container.innerHTML = '<span>Loading ad...</span>';
    
    // Request ad from API
    const requestUrl = `${domain}/api/rtb/request.php?token=${zoneToken}&format=banner&size=${size}`;
    
    fetch(requestUrl)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Display the ad
                container.innerHTML = data.content;
                container.style.border = 'none';
                container.style.background = 'transparent';
                
                // Track impression
                trackEvent('impression', {
                    zone_id: zoneToken,
                    campaign_id: data.campaign_id,
                    type: data.type
                });
                
                // Add click tracking
                if (data.click_url || data.type === 'ron') {
                    addClickTracking(container, {
                        zone_id: zoneToken,
                        campaign_id: data.campaign_id,
                        type: data.type
                    });
                }
            } else {
                // Show fallback message
                container.innerHTML = '<span style="font-size: 12px; color: #999;">No ads available</span>';
            }
        })
        .catch(error => {
            console.error('AdZone error:', error);
            container.innerHTML = '<span style="font-size: 12px; color: #cc0000;">Ad loading failed</span>';
        });
    
    function trackEvent(event, data) {
        const trackingUrl = `${domain}/api/track/${event}.php`;
        const formData = new FormData();
        
        Object.keys(data).forEach(key => {
            formData.append(key, data[key]);
        });
        formData.append('timestamp', Date.now());
        
        fetch(trackingUrl, {
            method: 'POST',
            body: formData
        }).catch(error => {
            console.warn(`${event} tracking failed:`, error);
        });
    }
    
    function addClickTracking(container, data) {
        const clickableElements = container.querySelectorAll('a, [onclick], button, [data-click]');
        
        clickableElements.forEach(element => {
            element.addEventListener('click', function(e) {
                trackEvent('click', data);
            });
        });
        
        // Also track clicks on the container itself
        container.addEventListener('click', function(e) {
            if (e.target !== container) return;
            trackEvent('click', data);
        });
    }
})();