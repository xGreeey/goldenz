/**
 * Realistic Rain Animation with Lightning & Thunderstorm
 * Golden Z-5 HR System - Login Page
 * 
 * Creates and manages realistic rain droplets with:
 * - Multiple layers for depth
 * - Varying speeds and sizes
 * - Splash effects
 * - Lightning flashes
 * - Thunder sound effects
 * - Umbrella cursor integration
 * - Performance optimization
 */

(function() {
    'use strict';
    
    // Configuration
    const CONFIG = {
        // Number of drops per layer (adjusted for optimal performance)
        FAR_LAYER_DROPS: 100,
        MIDDLE_LAYER_DROPS: 150,
        NEAR_LAYER_DROPS: 200,
        
        // Animation speeds (will be randomized)
        MIN_SPEED: 0.8,
        MAX_SPEED: 1.5,
        
        // Splash generation
        SPLASH_CHANCE: 0.4, // 40% chance of splash
        SPLASH_INTERVAL: 40, // Generate splash every 40ms
        
        // Lightning & Thunder
        LIGHTNING_MIN_INTERVAL: 3000, // Minimum 3 seconds between lightning
        LIGHTNING_MAX_INTERVAL: 8000, // Maximum 8 seconds between lightning
        LIGHTNING_FLASH_DURATION: 50, // Flash duration in ms
        LIGHTNING_FLASH_FADE: 200, // Fade out duration in ms
        THUNDER_DELAY_MIN: 500, // Minimum delay before thunder (ms)
        THUNDER_DELAY_MAX: 2000, // Maximum delay before thunder (ms)
        
        // Performance
        MAX_DROPS: 500,
        CLEANUP_INTERVAL: 5000, // Clean up old splashes every 5 seconds
    };
    
    let rainContainer = null;
    let splashContainer = null;
    let lightningFlash = null;
    let isInitialized = false;
    let lightningTimeout = null;
    let audioContext = null;
    
    /**
     * Create thunder sound using Web Audio API
     */
    function createThunderSound() {
        try {
            // Create audio context if not exists
            if (!audioContext) {
                audioContext = new (window.AudioContext || window.webkitAudioContext)();
            }
            
            // Create oscillator for thunder rumble
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            
            // Connect nodes
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            // Thunder characteristics
            oscillator.type = 'sawtooth';
            oscillator.frequency.setValueAtTime(60, audioContext.currentTime);
            oscillator.frequency.exponentialRampToValueAtTime(30, audioContext.currentTime + 0.5);
            oscillator.frequency.exponentialRampToValueAtTime(20, audioContext.currentTime + 1);
            
            // Volume envelope (fade in and out)
            gainNode.gain.setValueAtTime(0, audioContext.currentTime);
            gainNode.gain.linearRampToValueAtTime(0.3, audioContext.currentTime + 0.1);
            gainNode.gain.exponentialRampToValueAtTime(0.1, audioContext.currentTime + 0.5);
            gainNode.gain.exponentialRampToValueAtTime(0, audioContext.currentTime + 2);
            
            // Start and stop
            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 2);
            
            // Add some noise for realism
            const bufferSize = audioContext.sampleRate * 0.5;
            const noiseBuffer = audioContext.createBuffer(1, bufferSize, audioContext.sampleRate);
            const output = noiseBuffer.getChannelData(0);
            
            for (let i = 0; i < bufferSize; i++) {
                output[i] = Math.random() * 2 - 1;
            }
            
            const noise = audioContext.createBufferSource();
            const noiseGain = audioContext.createGain();
            noise.buffer = noiseBuffer;
            noise.connect(noiseGain);
            noiseGain.connect(audioContext.destination);
            noiseGain.gain.setValueAtTime(0, audioContext.currentTime);
            noiseGain.gain.linearRampToValueAtTime(0.1, audioContext.currentTime + 0.05);
            noiseGain.gain.exponentialRampToValueAtTime(0, audioContext.currentTime + 0.3);
            
            noise.start(audioContext.currentTime);
            noise.stop(audioContext.currentTime + 0.3);
            
        } catch (error) {
            console.warn('Could not create thunder sound:', error);
        }
    }
    
    /**
     * Create lightning flash effect
     */
    function createLightningFlash() {
        if (!lightningFlash) {
            // Create flash overlay
            lightningFlash = document.createElement('div');
            lightningFlash.className = 'lightning-flash';
            document.body.appendChild(lightningFlash);
        }
        
        // Random position for lightning bolt (optional visual bolt)
        const boltX = Math.random() * 100;
        const boltY = Math.random() * 30; // Top 30% of screen
        
        // Create lightning bolt SVG
        const bolt = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        bolt.setAttribute('class', 'lightning-bolt');
        bolt.setAttribute('width', '200');
        bolt.setAttribute('height', '400');
        bolt.setAttribute('viewBox', '0 0 200 400');
        bolt.style.left = boltX + '%';
        bolt.style.top = boltY + '%';
        bolt.style.width = '200px';
        bolt.style.height = '400px';
        
        // Lightning bolt path
        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        path.setAttribute('d', 'M100,0 L80,100 L100,120 L60,200 L100,220 L40,300 L100,320 L20,400');
        path.setAttribute('fill', 'none');
        path.setAttribute('stroke', '#FFD700');
        path.setAttribute('stroke-width', '4');
        path.setAttribute('stroke-linecap', 'round');
        path.setAttribute('stroke-linejoin', 'round');
        path.style.filter = 'drop-shadow(0 0 10px #FFD700) drop-shadow(0 0 20px #FFA500)';
        
        bolt.appendChild(path);
        document.body.appendChild(bolt);
        
        // Flash effect
        lightningFlash.classList.add('active');
        bolt.classList.add('active');
        
        // Remove flash quickly
        setTimeout(() => {
            lightningFlash.classList.remove('active');
            lightningFlash.classList.add('fade-out');
            bolt.classList.remove('active');
            bolt.classList.add('fade-out');
            
            // Remove bolt after animation
            setTimeout(() => {
                if (bolt.parentNode) {
                    bolt.parentNode.removeChild(bolt);
                }
                lightningFlash.classList.remove('fade-out');
            }, CONFIG.LIGHTNING_FLASH_FADE);
        }, CONFIG.LIGHTNING_FLASH_DURATION);
        
        // Play thunder sound after delay
        const thunderDelay = CONFIG.THUNDER_DELAY_MIN + 
            Math.random() * (CONFIG.THUNDER_DELAY_MAX - CONFIG.THUNDER_DELAY_MIN);
        
        setTimeout(() => {
            createThunderSound();
        }, thunderDelay);
    }
    
    /**
     * Schedule next lightning strike
     */
    function scheduleLightning() {
        if (!isInitialized) return;
        
        const delay = CONFIG.LIGHTNING_MIN_INTERVAL + 
            Math.random() * (CONFIG.LIGHTNING_MAX_INTERVAL - CONFIG.LIGHTNING_MIN_INTERVAL);
        
        lightningTimeout = setTimeout(() => {
            createLightningFlash();
            scheduleLightning(); // Schedule next one
        }, delay);
    }
    
    /**
     * Create rain container structure
     */
    function createRainContainer() {
        const container = document.createElement('div');
        container.className = 'rain-container';
        container.setAttribute('aria-hidden', 'true');
        
        // Create three layers
        const farLayer = document.createElement('div');
        farLayer.className = 'rain-layer far';
        
        const middleLayer = document.createElement('div');
        middleLayer.className = 'rain-layer middle';
        
        const nearLayer = document.createElement('div');
        nearLayer.className = 'rain-layer near';
        
        // Splash container
        const splash = document.createElement('div');
        splash.className = 'rain-splash-container';
        splash.style.position = 'absolute';
        splash.style.bottom = '0';
        splash.style.left = '0';
        splash.style.width = '100%';
        splash.style.height = '100px';
        splash.style.pointerEvents = 'none';
        splash.style.overflow = 'hidden';
        
        container.appendChild(farLayer);
        container.appendChild(middleLayer);
        container.appendChild(nearLayer);
        container.appendChild(splash);
        
        return {
            container,
            farLayer,
            middleLayer,
            nearLayer,
            splashContainer: splash
        };
    }
    
    /**
     * Create a single rain drop
     * @param {HTMLElement} layer - The layer to add the drop to
     * @param {string} layerType - 'far', 'middle', or 'near'
     */
    function createRainDrop(layer, layerType) {
        const drop = document.createElement('div');
        drop.className = 'rain-drop';
        
        // Random horizontal position
        const left = Math.random() * 100;
        drop.style.left = left + '%';
        
        // Random delay for staggered effect
        const delay = Math.random() * 2;
        drop.style.animationDelay = delay + 's';
        
        // Random speed variation for more realism
        const speedMultiplier = CONFIG.MIN_SPEED + Math.random() * (CONFIG.MAX_SPEED - CONFIG.MIN_SPEED);
        const baseDuration = layerType === 'far' ? 2 : (layerType === 'middle' ? 1.5 : 1);
        drop.style.animationDuration = (baseDuration / speedMultiplier) + 's';
        
        // Random slight rotation for realism (wind effect)
        const rotation = (Math.random() - 0.5) * 8; // -4 to +4 degrees
        drop.style.transform = `rotate(${rotation}deg)`;
        
        // Vary drop size slightly for more realism
        const sizeVariation = 0.8 + Math.random() * 0.4; // 80% to 120% of base size
        if (layerType === 'far') {
            drop.style.height = (15 * sizeVariation) + 'px';
            drop.style.width = (1 * sizeVariation) + 'px';
        } else if (layerType === 'middle') {
            drop.style.height = (18 * sizeVariation) + 'px';
            drop.style.width = (1.5 * sizeVariation) + 'px';
        } else {
            drop.style.height = (25 * sizeVariation) + 'px';
            drop.style.width = (2 * sizeVariation) + 'px';
        }
        
        // Add wind effect randomly (stronger wind for near layer)
        if (Math.random() > (layerType === 'near' ? 0.5 : 0.7)) {
            drop.classList.add(Math.random() > 0.5 ? 'wind-left' : 'wind-right');
        }
        
        // Vary opacity for depth
        const opacity = layerType === 'far' ? 0.3 + Math.random() * 0.2 : 
                       (layerType === 'middle' ? 0.5 + Math.random() * 0.2 : 0.7 + Math.random() * 0.2);
        drop.style.opacity = opacity;
        
        layer.appendChild(drop);
        return drop;
    }
    
    /**
     * Generate rain drops for all layers
     */
    function generateRainDrops(rainStructure) {
        // Far layer
        for (let i = 0; i < CONFIG.FAR_LAYER_DROPS; i++) {
            createRainDrop(rainStructure.farLayer, 'far');
        }
        
        // Middle layer
        for (let i = 0; i < CONFIG.MIDDLE_LAYER_DROPS; i++) {
            createRainDrop(rainStructure.middleLayer, 'middle');
        }
        
        // Near layer
        for (let i = 0; i < CONFIG.NEAR_LAYER_DROPS; i++) {
            createRainDrop(rainStructure.nearLayer, 'near');
        }
    }
    
    /**
     * Create a splash effect
     * @param {number} x - Horizontal position (0-100)
     */
    function createSplash(x) {
        if (!splashContainer) return;
        
        // Create multiple splash particles for more realism
        const splashCount = 2 + Math.floor(Math.random() * 3); // 2-4 particles
        
        for (let i = 0; i < splashCount; i++) {
            const splash = document.createElement('div');
            splash.className = 'rain-splash';
            
            // Vary position slightly
            const offsetX = (Math.random() - 0.5) * 20; // -10px to +10px
            splash.style.left = `calc(${x}% + ${offsetX}px)`;
            
            // Vary size
            const size = 4 + Math.random() * 6; // 4px to 10px
            splash.style.width = size + 'px';
            splash.style.height = (size * 0.5) + 'px';
            
            // Vary animation delay for staggered effect
            splash.style.animationDelay = (Math.random() * 0.1) + 's';
            
            // Vary opacity
            splash.style.opacity = 0.4 + Math.random() * 0.4;
            
            splashContainer.appendChild(splash);
            
            // Remove splash after animation
            setTimeout(() => {
                if (splash.parentNode) {
                    splash.parentNode.removeChild(splash);
                }
            }, 300);
        }
    }
    
    /**
     * Generate random splashes at the bottom
     */
    function generateSplashes() {
        if (Math.random() < CONFIG.SPLASH_CHANCE) {
            const x = Math.random() * 100;
            createSplash(x);
        }
    }
    
    /**
     * Clean up old splashes
     */
    function cleanupSplashes() {
        if (!splashContainer) return;
        
        const splashes = splashContainer.querySelectorAll('.rain-splash');
        if (splashes.length > 50) {
            // Remove oldest splashes
            Array.from(splashes).slice(0, splashes.length - 50).forEach(splash => {
                if (splash.parentNode) {
                    splash.parentNode.removeChild(splash);
                }
            });
        }
    }
    
    /**
     * Initialize rain animation
     */
    function initRainAnimation() {
        if (isInitialized) return;
        
        // Check for reduced motion preference
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }
        
        // Create rain structure
        const rainStructure = createRainContainer();
        rainContainer = rainStructure.container;
        splashContainer = rainStructure.splashContainer;
        
        // Add to page
        const loginContainer = document.querySelector('.login-split-container');
        if (loginContainer) {
            loginContainer.appendChild(rainContainer);
        } else {
            document.body.appendChild(rainContainer);
        }
        
        // Add umbrella cursor class to body
        document.body.classList.add('rain-active');
        
        // Generate rain drops
        generateRainDrops(rainStructure);
        
        // Start splash generation
        const splashInterval = setInterval(() => {
            generateSplashes();
        }, CONFIG.SPLASH_INTERVAL);
        
        // Cleanup interval
        const cleanupInterval = setInterval(() => {
            cleanupSplashes();
        }, CONFIG.CLEANUP_INTERVAL);
        
        // Store intervals for cleanup
        rainContainer._splashInterval = splashInterval;
        rainContainer._cleanupInterval = cleanupInterval;
        
        // Start lightning and thunder
        scheduleLightning();
        
        isInitialized = true;
        
        console.log('⛈️ Thunderstorm animation initialized with lightning and thunder');
    }
    
    /**
     * Destroy rain animation
     */
    function destroyRainAnimation() {
        if (!isInitialized || !rainContainer) return;
        
        // Clear lightning timeout
        if (lightningTimeout) {
            clearTimeout(lightningTimeout);
            lightningTimeout = null;
        }
        
        // Clear intervals
        if (rainContainer._splashInterval) {
            clearInterval(rainContainer._splashInterval);
        }
        if (rainContainer._cleanupInterval) {
            clearInterval(rainContainer._cleanupInterval);
        }
        
        // Remove container
        if (rainContainer.parentNode) {
            rainContainer.parentNode.removeChild(rainContainer);
        }
        
        // Remove lightning flash
        if (lightningFlash && lightningFlash.parentNode) {
            lightningFlash.parentNode.removeChild(lightningFlash);
        }
        
        // Remove umbrella cursor class
        document.body.classList.remove('rain-active');
        
        rainContainer = null;
        splashContainer = null;
        lightningFlash = null;
        isInitialized = false;
    }
    
    /**
     * Auto-initialize when DOM is ready
     */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initRainAnimation);
    } else {
        initRainAnimation();
    }
    
    // Expose API
    window.RainAnimation = {
        init: initRainAnimation,
        destroy: destroyRainAnimation,
        isActive: () => isInitialized
    };
    
})();
