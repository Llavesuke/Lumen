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
      
      // Main portal ring
      const ringGeometry = new THREE.TorusGeometry(2, 0.3, 16, 100);
      const ringMaterial = new THREE.MeshStandardMaterial({
        color: 0xF58216, // Orange color like Doctor Strange portals
        emissive: 0xF58216,
        emissiveIntensity: 0.6,
        metalness: 0.7,
        roughness: 0.3
      });
      
      const ring = new THREE.Mesh(ringGeometry, ringMaterial);
      portal.add(ring);
      
      // Secondary ring (outer glow)
      const outerRingGeometry = new THREE.TorusGeometry(2.2, 0.1, 16, 100);
      const outerRingMaterial = new THREE.MeshBasicMaterial({
        color: 0xFFA500, // Bright orange
        transparent: true,
        opacity: 0.6
      });
      
      const outerRing = new THREE.Mesh(outerRingGeometry, outerRingMaterial);
      portal.add(outerRing);
      
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
      
      // Create magical runes
      const createRunes = () => {
        // Create canvas for rune generation
        const generateRuneTexture = () => {
          const canvas = document.createElement('canvas');
          canvas.width = 64;
          canvas.height = 64;
          const ctx = canvas.getContext('2d');
          
          // Clear canvas
          ctx.fillStyle = 'rgba(0,0,0,0)';
          ctx.fillRect(0, 0, canvas.width, canvas.height);
          
          // Draw rune (simplified magical symbols)
          ctx.strokeStyle = `rgba(255, ${130 + Math.random() * 125}, 0, 0.8)`;
          ctx.lineWidth = 2;
          
          // Random rune pattern
          ctx.beginPath();
          
          // Different rune patterns
          const patternType = Math.floor(Math.random() * 5);
          
          switch(patternType) {
            case 0: // Circle with lines
              ctx.arc(32, 32, 20, 0, Math.PI * 2);
              ctx.moveTo(12, 32);
              ctx.lineTo(52, 32);
              ctx.moveTo(32, 12);
              ctx.lineTo(32, 52);
              break;
            case 1: // Triangle
              ctx.moveTo(32, 12);
              ctx.lineTo(52, 52);
              ctx.lineTo(12, 52);
              ctx.closePath();
              break;
            case 2: // Square with diagonals
              ctx.rect(12, 12, 40, 40);
              ctx.moveTo(12, 12);
              ctx.lineTo(52, 52);
              ctx.moveTo(52, 12);
              ctx.lineTo(12, 52);
              break;
            case 3: // Spiral
              for (let i = 0; i < 10; i++) {
                const radius = 5 + i * 2;
                const angle = i * Math.PI / 5;
                const x = 32 + radius * Math.cos(angle);
                const y = 32 + radius * Math.sin(angle);
                if (i === 0) ctx.moveTo(x, y);
                else ctx.lineTo(x, y);
              }
              break;
            case 4: // Star
              for (let i = 0; i < 5; i++) {
                const outerRadius = 25;
                const innerRadius = 10;
                const outerAngle = i * Math.PI * 2 / 5;
                const innerAngle = outerAngle + Math.PI / 5;
                
                const outerX = 32 + outerRadius * Math.cos(outerAngle);
                const outerY = 32 + outerRadius * Math.sin(outerAngle);
                const innerX = 32 + innerRadius * Math.cos(innerAngle);
                const innerY = 32 + innerRadius * Math.sin(innerAngle);
                
                if (i === 0) ctx.moveTo(outerX, outerY);
                else ctx.lineTo(outerX, outerY);
                
                ctx.lineTo(innerX, innerY);
              }
              ctx.closePath();
              break;
          }
          
          ctx.stroke();
          
          // Add some dots
          ctx.fillStyle = `rgba(255, ${150 + Math.random() * 105}, 50, 0.9)`;
          for (let i = 0; i < 5; i++) {
            const x = 10 + Math.random() * 44;
            const y = 10 + Math.random() * 44;
            const radius = 1 + Math.random() * 2;
            ctx.beginPath();
            ctx.arc(x, y, radius, 0, Math.PI * 2);
            ctx.fill();
          }
          
          return canvas;
        };
        
        // Generate several rune textures
        for (let i = 0; i < 8; i++) {
          const runeCanvas = generateRuneTexture();
          const runeTexture = new THREE.CanvasTexture(runeCanvas);
          runeTextures.push(runeTexture);
        }
        
        // Create runes around the portal
        for (let i = 0; i < 12; i++) {
          const angle = (i / 12) * Math.PI * 2;
          const radius = 2.1 + Math.random() * 0.2;
          
          const runeGeometry = new THREE.PlaneGeometry(0.4, 0.4);
          const runeMaterial = new THREE.MeshBasicMaterial({
            map: runeTextures[i % runeTextures.length],
            transparent: true,
            opacity: 0.9,
            side: THREE.DoubleSide
          });
          
          const rune = new THREE.Mesh(runeGeometry, runeMaterial);
          rune.position.x = Math.cos(angle) * radius;
          rune.position.y = Math.sin(angle) * radius;
          rune.position.z = 0.1;
          rune.userData = { 
            angle: angle,
            radius: radius,
            rotationSpeed: 0.01 + Math.random() * 0.01,
            pulseSpeed: 0.003 + Math.random() * 0.002
          };
          
          portal.add(rune);
          runes.push(rune);
        }
      };
      
      createRunes();
      
      // Create particles
      const createParticles = () => {
        const particleCount = 200;
        const particleGeometry = new THREE.BufferGeometry();
        const particlePositions = new Float32Array(particleCount * 3);
        const particleSizes = new Float32Array(particleCount);
        const particleColors = new Float32Array(particleCount * 3);
        
        for (let i = 0; i < particleCount; i++) {
          // Random position in a circular area around the portal
          const angle = Math.random() * Math.PI * 2;
          const radius = 1.5 + Math.random() * 1.5;
          
          particlePositions[i * 3] = Math.cos(angle) * radius;
          particlePositions[i * 3 + 1] = Math.sin(angle) * radius;
          particlePositions[i * 3 + 2] = (Math.random() - 0.5) * 0.5;
          
          // Random size
          particleSizes[i] = 0.03 + Math.random() * 0.05;
          
          // Color (orange to yellow)
          particleColors[i * 3] = 1.0; // R
          particleColors[i * 3 + 1] = 0.3 + Math.random() * 0.4; // G
          particleColors[i * 3 + 2] = 0.0; // B
        }
        
        particleGeometry.setAttribute('position', new THREE.BufferAttribute(particlePositions, 3));
        particleGeometry.setAttribute('size', new THREE.BufferAttribute(particleSizes, 1));
        particleGeometry.setAttribute('color', new THREE.BufferAttribute(particleColors, 3));
        
        // Particle shader material
        const particleMaterial = new THREE.ShaderMaterial({
          uniforms: {
            time: { value: 0.0 }
          },
          vertexShader: `
            attribute float size;
            attribute vec3 color;
            varying vec3 vColor;
            uniform float time;
            
            void main() {
              vColor = color;
              vec3 pos = position;
              
              // Add some movement
              float angle = atan(pos.y, pos.x);
              float radius = length(pos.xy);
              float speed = (1.5 - min(radius, 1.5)) * 0.3;
              
              // Spiral movement
              angle += time * speed;
              pos.x = cos(angle) * radius;
              pos.y = sin(angle) * radius;
              
              // Pulsing size
              float pulseFactor = 1.0 + 0.2 * sin(time * 2.0 + angle * 3.0);
              
              vec4 mvPosition = modelViewMatrix * vec4(pos, 1.0);
              gl_PointSize = size * pulseFactor * (300.0 / -mvPosition.z);
              gl_Position = projectionMatrix * mvPosition;
            }
          `,
          fragmentShader: `
            varying vec3 vColor;
            
            void main() {
              // Create circular particles
              float r = length(gl_PointCoord - vec2(0.5));
              if (r > 0.5) discard;
              
              // Soft edge
              float alpha = 1.0 - smoothstep(0.3, 0.5, r);
              gl_FragColor = vec4(vColor, alpha);
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
        
        // Update particles
        if (particles && particles.material.uniforms) {
          particles.material.uniforms.time.value += 0.01;
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