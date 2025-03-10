<script>
import { onMounted, onBeforeUnmount, ref } from 'vue';
import * as THREE from 'three';
import { useRoute } from 'vue-router';
import '@fortawesome/fontawesome-free/css/all.min.css';

export default {
  name: 'NotFoundPage',
  setup() {
    let scene, camera, renderer, portal, portalLight, particles;
    let runeTextures = [];
    let runes = [];
    let animationFrameId, handleResize, handleMouseMove;
    const route = useRoute();
    const isLoading = ref(true);
    
    const initThree = () => {
      // Create scene
      scene = new THREE.Scene();
      
      // Create camera
      camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 0.1, 1000);
      camera.position.z = 5;
      
      // Create renderer
      renderer = new THREE.WebGLRenderer({ 
        alpha: true, 
        antialias: true,
        powerPreference: 'high-performance'
      });
      renderer.setSize(window.innerWidth, window.innerHeight);
      renderer.setClearColor(0x000000, 0);
      
      const container = document.getElementById('three-container');
      if (container) {
        container.appendChild(renderer.domElement);
      }

      // Create portal effect (Doctor Strange style)
      portal = new THREE.Group();
      
      // Making rings almost invisible as per request - particles will form the circular shape
      // Very subtle main ring with minimal visibility
      const ringGeometry = new THREE.TorusGeometry(2, 0.05, 24, 120); // Much thinner
      const ringMaterial = new THREE.MeshBasicMaterial({
        color: 0xF58216, 
        transparent: true,
        opacity: 0.1, // Almost invisible
        blending: THREE.AdditiveBlending
      });
      
      const ring = new THREE.Mesh(ringGeometry, ringMaterial);
      portal.add(ring);
      
      // Very subtle outer glow - barely visible
      const outerRingGeometry = new THREE.TorusGeometry(2.2, 0.03, 24, 120);
      const outerRingMaterial = new THREE.MeshBasicMaterial({
        color: 0xFFA500,
        transparent: true,
        opacity: 0.08,
        blending: THREE.AdditiveBlending
      });
      
      const outerRing = new THREE.Mesh(outerRingGeometry, outerRingMaterial);
      portal.add(outerRing);
      
      // Very subtle inner glow - barely visible
      const innerRingGeometry = new THREE.TorusGeometry(1.9, 0.02, 24, 120);
      const innerRingMaterial = new THREE.MeshBasicMaterial({
        color: 0xFFD700,
        transparent: true,
        opacity: 0.08,
        blending: THREE.AdditiveBlending
      });
      
      const innerRing = new THREE.Mesh(innerRingGeometry, innerRingMaterial);
      portal.add(innerRing);
      
      // Portal inner
      const innerGeometry = new THREE.CircleGeometry(1.9, 64);
      const innerMaterial = new THREE.MeshBasicMaterial({
        color: 0x000000,
        transparent: true,
        opacity: 0.9
      });
      
      const inner = new THREE.Mesh(innerGeometry, innerMaterial);
      inner.position.z = -0.1;
      portal.add(inner);
      
      // Add subtle glow
      const glowGeometry = new THREE.CircleGeometry(2.5, 64);
      const glowMaterial = new THREE.MeshBasicMaterial({
        color: 0xF58216,
        transparent: true,
        opacity: 0.15
      });
      
      const glow = new THREE.Mesh(glowGeometry, glowMaterial);
      glow.position.z = -0.2;
      portal.add(glow);
      
      // We've removed the runes as per request to make the portal cleaner
      // and let the particles form the circular shape naturally
      
      // Create particles
      const createParticles = () => {
        // Increase particle count for more density
        const particleCount = 1200; // Increased for better circle formation
        const particleGeometry = new THREE.BufferGeometry();
        const particlePositions = new Float32Array(particleCount * 3);
        const particleSizes = new Float32Array(particleCount);
        const particleColors = new Float32Array(particleCount * 3);
        const particleVelocities = new Float32Array(particleCount * 3);
        const particleLifetimes = new Float32Array(particleCount);
        
        // Create a more even distribution of particles around the circle
        for (let i = 0; i < particleCount; i++) {
          // Create two types of particles: ring particles and spark particles
          const isRingParticle = i < particleCount * 0.85; // 85% ring particles to form a clearer circle
          
          if (isRingParticle) {
            // For ring particles, create a more uniform distribution around the circle
            // This helps form a more continuous circular shape
            const angle = (i / (particleCount * 0.85)) * Math.PI * 2;
            
            // Smaller radius variation for a more defined circle
            const radiusVariation = (Math.random() * 0.25 - 0.125); // Smaller variation
            const radius = 2.0 + radiusVariation;
            
            particlePositions[i * 3] = Math.cos(angle) * radius;
            particlePositions[i * 3 + 1] = Math.sin(angle) * radius;
            particlePositions[i * 3 + 2] = (Math.random() - 0.5) * 0.1; // Reduced depth variation
            
            // Varied sizes for more natural look but still forming a circle
            particleSizes[i] = 0.025 + Math.random() * 0.035;
            
            // Golden-orange colors with less variation for a more consistent look
            particleColors[i * 3] = 1.0; // R
            particleColors[i * 3 + 1] = 0.5 + Math.random() * 0.25; // G (more consistent golden)
            particleColors[i * 3 + 2] = Math.random() * 0.05; // Minimal blue
            
            // Minimal velocity for ring particles to maintain the circle shape
            particleVelocities[i * 3] = (Math.random() - 0.5) * 0.005;
            particleVelocities[i * 3 + 1] = (Math.random() - 0.5) * 0.005;
            particleVelocities[i * 3 + 2] = (Math.random() - 0.5) * 0.005;
            
            // Longer lifetime for ring particles to reduce regeneration frequency
            particleLifetimes[i] = 0.9 + Math.random() * 0.1;
          } else {
            // Spark particles - emanating from the ring
            // Random angle for natural distribution
            const angle = Math.random() * Math.PI * 2;
            const radius = 2.0 + (Math.random() * 0.05 - 0.025); // Start very close to the ring
            
            particlePositions[i * 3] = Math.cos(angle) * radius;
            particlePositions[i * 3 + 1] = Math.sin(angle) * radius;
            particlePositions[i * 3 + 2] = (Math.random() - 0.5) * 0.2;
            
            // Varied sizes for spark particles
            particleSizes[i] = 0.02 + Math.random() * 0.04;
            
            // Brighter colors for sparks
            particleColors[i * 3] = 1.0; // R
            particleColors[i * 3 + 1] = 0.7 + Math.random() * 0.3; // G (brighter)
            particleColors[i * 3 + 2] = 0.05 + Math.random() * 0.1; // Slight blue
            
            // Gentler outward velocity for sparks
            const speed = 0.01 + Math.random() * 0.02; // Slower for smoother effect
            particleVelocities[i * 3] = Math.cos(angle) * speed;
            particleVelocities[i * 3 + 1] = Math.sin(angle) * speed;
            particleVelocities[i * 3 + 2] = (Math.random() - 0.5) * 0.01;
            
            // Longer lifetime for spark particles to reduce visible resets
            particleLifetimes[i] = 0.5 + Math.random() * 0.5;
          }
        }
        
        // Add the new attributes to the geometry
        particleGeometry.setAttribute('position', new THREE.BufferAttribute(particlePositions, 3));
        particleGeometry.setAttribute('size', new THREE.BufferAttribute(particleSizes, 1));
        particleGeometry.setAttribute('color', new THREE.BufferAttribute(particleColors, 3));
        particleGeometry.setAttribute('velocity', new THREE.BufferAttribute(particleVelocities, 3));
        particleGeometry.setAttribute('lifetime', new THREE.BufferAttribute(particleLifetimes, 1));
        
        // Particle shader material with improved effects
        const particleMaterial = new THREE.ShaderMaterial({
          uniforms: {
            time: { value: 0.0 },
            pixelRatio: { value: window.devicePixelRatio }
          },
          vertexShader: `
            attribute float size;
            attribute vec3 color;
            attribute vec3 velocity;
            attribute float lifetime;
            varying vec3 vColor;
            varying float vLifetime;
            uniform float time;
            uniform float pixelRatio;
            
            // Noise functions for more natural movement
            float random(vec2 st) {
                return fract(sin(dot(st.xy, vec2(12.9898, 78.233))) * 43758.5453123);
            }
            
            void main() {
              vColor = color;
              vLifetime = lifetime;
              
              // Base position with velocity applied
              vec3 pos = position;
              
              // Use continuous time without resetting to prevent jarring effects
              // Instead of cycling, we'll use a continuous flow with individual particle timing
              float particleOffset = random(vec2(position.x * 0.1, position.y * 0.1)) * 100.0; // Unique offset per particle
              float continuousTime = time + particleOffset;
              float particleTime = fract(continuousTime * 0.1 / lifetime); // Smoother cycle
              
              // Apply velocity with continuous time for smoother movement
              pos += velocity * continuousTime * 0.2; // Reduced velocity impact
              
              // Add some turbulence based on position and time
              float turbulence = sin(continuousTime * 0.5 + length(pos.xy) * 2.0) * 0.02;
              
              // Calculate angle and radius for circular movement
              float angle = atan(pos.y, pos.x);
              float radius = length(pos.xy);
              
              // For ring particles, maintain the circular shape more strictly
              if (lifetime > 0.7) { // Ring particles have longer lifetime
                // Keep particles closer to the ideal ring radius (2.0)
                float idealRadius = 2.0;
                float radiusCorrection = (idealRadius - radius) * 0.03;
                radius += radiusCorrection;
                
                // Add very subtle spiral movement
                float spiralSpeed = 0.05 * random(vec2(angle, radius));
                angle += continuousTime * spiralSpeed;
                
                // Recalculate position with corrected radius and angle
                pos.x = cos(angle) * radius;
                pos.y = sin(angle) * radius;
                
                // Add extremely subtle radial pulsing that doesn't break the circle
                radius += sin(continuousTime * 0.2 + angle * 2.0) * 0.01;
                pos.x = cos(angle) * radius;
                pos.y = sin(angle) * radius;
              } else {
                // For spark particles, add smooth outward movement
                // Use continuous timing to prevent visible resets
                if (particleTime > 0.9) {
                  // Smooth fade out at the end of life
                  vColor *= smoothstep(1.0, 0.9, particleTime);
                }
              }
              
              // Add turbulence
              pos.x += turbulence * sin(time * 4.0 + pos.y * 10.0);
              pos.y += turbulence * cos(time * 3.0 + pos.x * 10.0);
              
              // Pulsing size effect based on particle type
              float pulseFactor;
              if (lifetime > 0.7) {
                // Subtle pulse for ring particles
                pulseFactor = 1.0 + 0.15 * sin(time * 3.0 + angle * 5.0);
              } else {
                // Stronger pulse for spark particles
                pulseFactor = 1.0 + 0.3 * sin(time * 5.0 + random(vec2(angle, radius)) * 10.0);
              }
              
              vec4 mvPosition = modelViewMatrix * vec4(pos, 1.0);
              gl_PointSize = size * pulseFactor * (350.0 / -mvPosition.z) * pixelRatio;
              gl_Position = projectionMatrix * mvPosition;
            }
          `,
          fragmentShader: `
            varying vec3 vColor;
            varying float vLifetime;
            
            void main() {
              // Create better looking particles with soft glow
              vec2 uv = gl_PointCoord.xy - 0.5;
              float r = length(uv);
              
              // Different particle appearance based on lifetime
              if (vLifetime > 0.7) {
                // Ring particles - softer glow
                if (r > 0.5) discard;
                
                // Gradient from center to edge
                float glow = 1.0 - smoothstep(0.2, 0.5, r);
                
                // Add inner brightness
                float innerGlow = smoothstep(0.4, 0.0, r);
                vec3 finalColor = mix(vColor, vec3(1.0, 0.9, 0.6), innerGlow * 0.6);
                
                // Soft edge
                float alpha = glow * 0.9;
                gl_FragColor = vec4(finalColor, alpha);
              } else {
                // Spark particles - sharper with trail effect
                if (r > 0.5) discard;
                
                // More intense center
                float intensity = 1.0 - smoothstep(0.0, 0.5, r);
                intensity = pow(intensity, 1.5);
                
                // Brighter core
                vec3 finalColor = mix(vColor, vec3(1.0, 0.95, 0.8), intensity * 0.7);
                
                // Sharper edge but still with some glow
                float alpha = intensity * 0.95;
                gl_FragColor = vec4(finalColor, alpha);
              }
            }
          `,
          transparent: true,
          depthWrite: false,
          blending: THREE.AdditiveBlending
        });
        
        particles = new THREE.Points(particleGeometry, particleMaterial);
        portal.add(particles);
      };
      
      createParticles();
      
      // Add portal to scene
      scene.add(portal);
      
      // Add ambient light
      const ambientLight = new THREE.AmbientLight(0xffffff, 0.4);
      scene.add(ambientLight);
      
      // Add directional light
      const directionalLight = new THREE.DirectionalLight(0xffffff, 0.8);
      directionalLight.position.set(5, 5, 5);
      scene.add(directionalLight);
      
      // Add point light in the center of the portal
      portalLight = new THREE.PointLight(0xF58216, 2, 6, 2);
      portalLight.position.set(0, 0, 1);
      portal.add(portalLight);
      
      // Handle window resize
      handleResize = () => {
        camera.aspect = window.innerWidth / window.innerHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(window.innerWidth, window.innerHeight);
      };
      
      window.addEventListener('resize', handleResize);
      
      // Handle mouse movement for interactive effect
      const mouse = { x: 0, y: 0 };
      
      handleMouseMove = (event) => {
        // Calculate mouse position in normalized device coordinates
        mouse.x = (event.clientX / window.innerWidth) * 2 - 1;
        mouse.y = -(event.clientY / window.innerHeight) * 2 + 1;
      };
      
      window.addEventListener('mousemove', handleMouseMove);
      
      // Animation loop
      const animate = () => {
        animationFrameId = requestAnimationFrame(animate);
        
        // Rotate portal
        portal.rotation.z += 0.002;
        
        // Update runes
        runes.forEach(rune => {
          // Rotate each rune
          rune.rotation.z += rune.userData.rotationSpeed;
          
          // Make runes pulse
          const time = Date.now() * 0.001;
          const pulse = Math.sin(time * rune.userData.pulseSpeed) * 0.1 + 0.9;
          rune.scale.set(pulse, pulse, 1);
          
          // Move runes slightly
          const newAngle = rune.userData.angle + time * 0.05;
          rune.position.x = Math.cos(newAngle) * rune.userData.radius;
          rune.position.y = Math.sin(newAngle) * rune.userData.radius;
        });
        
        // Update particles with smoother, more continuous behavior
        if (particles && particles.material.uniforms) {
          // Use a smaller increment for smoother time progression
          particles.material.uniforms.time.value += 0.005;
          
          // Only regenerate particles that are truly needed
          // This prevents the jarring reset effect by making regeneration more gradual
          const positions = particles.geometry.attributes.position.array;
          const velocities = particles.geometry.attributes.velocity.array;
          const colors = particles.geometry.attributes.color.array;
          const sizes = particles.geometry.attributes.size.array;
          const lifetimes = particles.geometry.attributes.lifetime.array;
          
          // Regenerate fewer particles each frame for a more continuous effect
          const particleCount = positions.length / 3;
          const regenerateCount = Math.floor(particleCount * 0.005); // Regenerate only 0.5% of particles each frame
          
          // Create a continuous distribution of particles around the circle
          // by selecting regeneration positions strategically
          for (let i = 0; i < regenerateCount; i++) {
            // Instead of completely random selection, use a more strategic approach
            // to maintain the circular shape
            const index = Math.floor(Math.random() * particleCount);
            const i3 = index * 3;
            
            // 80% chance to create ring particle, 20% chance for spark
            // Increasing ring particles to better form the circular shape
            const isRingParticle = Math.random() < 0.8;
            
            if (isRingParticle) {
              // Create a more uniform distribution around the circle
              // by using strategic angle placement
              const baseAngle = (i / regenerateCount) * Math.PI * 2; // Distribute evenly
              const angleVariation = (Math.random() * 0.2) - 0.1; // Small variation
              const angle = baseAngle + angleVariation;
              
              // Keep radius variations smaller to maintain a cleaner circle
              const radiusVariation = (Math.random() * 0.2) - 0.1; // Smaller variation
              const radius = 2.0 + radiusVariation;
              
              positions[i3] = Math.cos(angle) * radius;
              positions[i3 + 1] = Math.sin(angle) * radius;
              positions[i3 + 2] = (Math.random() - 0.5) * 0.1; // Reduced depth variation
              
              // Even more subtle velocity for ring particles
              // This helps maintain the circular shape
              velocities[i3] = (Math.random() - 0.5) * 0.005;
              velocities[i3 + 1] = (Math.random() - 0.5) * 0.005;
              velocities[i3 + 2] = (Math.random() - 0.5) * 0.005;
              
              // Golden-orange colors with less variation for consistency
              colors[i3] = 1.0;
              colors[i3 + 1] = 0.5 + Math.random() * 0.2; // More consistent gold
              colors[i3 + 2] = Math.random() * 0.05; // Less blue
              
              // More consistent sizes for ring particles
              sizes[index] = 0.025 + Math.random() * 0.03;
              // Longer lifetimes to reduce frequency of regeneration
              lifetimes[index] = 0.9 + Math.random() * 0.1;
            } else {
              // Spark particles - create them at specific points on the ring
              // to maintain the illusion of continuous emission
              const angle = Math.random() * Math.PI * 2;
              const radius = 2.0 + (Math.random() * 0.05 - 0.025); // Very close to the ring
              
              positions[i3] = Math.cos(angle) * radius;
              positions[i3 + 1] = Math.sin(angle) * radius;
              positions[i3 + 2] = (Math.random() - 0.5) * 0.2;
              
              // Gentler outward velocity for sparks
              const speed = 0.01 + Math.random() * 0.02; // Slower movement
              velocities[i3] = Math.cos(angle) * speed;
              velocities[i3 + 1] = Math.sin(angle) * speed;
              velocities[i3 + 2] = (Math.random() - 0.5) * 0.01;
              
              // Brighter colors for sparks
              colors[i3] = 1.0;
              colors[i3 + 1] = 0.7 + Math.random() * 0.3; // Brighter gold
              colors[i3 + 2] = 0.05 + Math.random() * 0.1; // Slight blue
              
              // Varied sizes for spark particles
              sizes[index] = 0.02 + Math.random() * 0.04;
              // Longer lifetimes for smoother transitions
              lifetimes[index] = 0.5 + Math.random() * 0.5;
            }
          }
          
          // Mark attributes as needing update
          particles.geometry.attributes.position.needsUpdate = true;
          particles.geometry.attributes.velocity.needsUpdate = true;
          particles.geometry.attributes.color.needsUpdate = true;
          particles.geometry.attributes.size.needsUpdate = true;
          particles.geometry.attributes.lifetime.needsUpdate = true;
        }
        
        // Make portal light flicker slightly
        if (portalLight) {
          portalLight.intensity = 2 + Math.sin(Date.now() * 0.005) * 0.3;
        }
        
        // Make portal respond to mouse movement
        if (mouse) {
          const targetX = mouse.x * 0.3;
          const targetY = mouse.y * 0.3;
          portal.rotation.x += (targetY - portal.rotation.x) * 0.05;
          portal.rotation.y += (targetX - portal.rotation.y) * 0.05;
        }
        
        renderer.render(scene, camera);
      };
      
      // Start animation
      animate();
      
      // Set loading to false after initialization
      setTimeout(() => {
        isLoading.value = false;
      }, 500);
    };
    
    onMounted(() => {
      // Initialize Three.js scene
      initThree();
    });
    
    onBeforeUnmount(() => {
      // Clean up resources
      if (animationFrameId) {
        cancelAnimationFrame(animationFrameId);
      }
      
      if (handleResize) {
        window.removeEventListener('resize', handleResize);
      }
      
      if (handleMouseMove) {
        window.removeEventListener('mousemove', handleMouseMove);
      }
      
      // Dispose of Three.js resources
      if (scene) {
        scene.traverse((object) => {
          if (object.geometry) {
            object.geometry.dispose();
          }
          
          if (object.material) {
            if (Array.isArray(object.material)) {
              object.material.forEach(material => material.dispose());
            } else {
              object.material.dispose();
            }
          }
        });
      }
      
      if (renderer) {
        renderer.dispose();
      }
    });
    
    return {
      isLoading
    };
  }
};
</script>

<template>
  <div class="not-found-page">
    <div id="three-container" class="portal-container"></div>
    
    <div class="content" :class="{ 'content--visible': !isLoading }">
      <h1 class="title">404</h1>
      <p class="subtitle">Has entrado en otra dimensión</p>
      <p class="message">La página que buscas no existe en este universo</p>
      
      <router-link to="/" class="home-button">
        <span class="home-button__text">Volver a la realidad</span>
        <span class="home-button__icon">
          <i class="fas fa-portal-exit"></i>
        </span>
      </router-link>
    </div>
  </div>
</template>

<style scoped>
.not-found-page {
  position: relative;
  width: 100%;
  height: 100vh;
  overflow: hidden;
  background-color: #0a0a0a;
  display: flex;
  justify-content: center;
  align-items: center;
}

.portal-container {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 1;
}

.content {
  position: relative;
  z-index: 2;
  text-align: center;
  color: white;
  max-width: 600px;
  padding: 2rem;
  opacity: 0;
  transform: translateY(20px);
  transition: opacity 0.8s ease, transform 0.8s ease;
}

.content--visible {
  opacity: 1;
  transform: translateY(0);
}

.title {
  font-size: 8rem;
  font-weight: 700;
  margin: 0;
  line-height: 1;
  background: linear-gradient(to right, #F58216, #FFA500);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
  text-shadow: 0 0 20px rgba(245, 130, 22, 0.5);
}

.subtitle {
  font-size: 2rem;
  font-weight: 600;
  margin: 0.5rem 0 1rem;
  color: #FFA500;
}

.message {
  font-size: 1.2rem;
  margin-bottom: 2rem;
  color: rgba(255, 255, 255, 0.8);
}

.home-button {
  display: inline-flex;
  align-items: center;
  padding: 0.8rem 1.5rem;
  background: linear-gradient(45deg, #F58216, #FFA500);
  color: white;
  text-decoration: none;
  border-radius: 30px;
  font-weight: 600;
  box-shadow: 0 5px 15px rgba(245, 130, 22, 0.4);
  transition: all 0.3s ease;
  border: none;
  cursor: pointer;
}

.home-button:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 20px rgba(245, 130, 22, 0.6);
}

.home-button__text {
  margin-right: 0.5rem;
}

.home-button__icon {
  font-size: 1.1rem;
}

/* Loading animation */
@keyframes pulse {
  0% { opacity: 0.6; transform: scale(0.98); }
  50% { opacity: 1; transform: scale(1); }
  100% { opacity: 0.6; transform: scale(0.98); }
}

/* Responsive styles */
@media (max-width: 768px) {
  .title {
    font-size: 6rem;
  }
  
  .subtitle {
    font-size: 1.5rem;
  }
  
  .message {
    font-size: 1rem;
  }
}

@media (max-width: 480px) {
  .title {
    font-size: 4rem;
  }
  
  .subtitle {
    font-size: 1.2rem;
  }
}
</style>