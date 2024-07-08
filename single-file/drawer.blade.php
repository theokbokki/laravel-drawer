<div data-role="drawer" id="{{ $id }}">
    <div data-role="drawer-background"></div>
    <div data-role="drawer-container">
        <div data-role="drawer-handle"></div>
        <div data-role="drawer-content">
            {{ $slot }}
        </div>
    </div>
</div>

<style>
    [data-role=drawer] {
        position: absolute;
        inset: 0;
        display: none;
    }

    [data-role=drawer-background] {
        content: "";
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, .5);
    }

    [data-role=drawer-container] {
        position: absolute;
        bottom: 0;
        top: 10vh;
        top: 10dvh;
        left: 0;
        right: 0;
        display: grid;
        padding: 2.25rem 0 0;
        background: white;
        border-top-right-radius: 1rem;
        border-top-left-radius: 1rem;
    }

    [data-role=drawer-container]:before {
        content: "";
        position: absolute;
        top: 100%;
        right: 0;
        bottom: initial;
        left: 0;
        height: 200%;
        background: inherit;
    }

    [data-role=drawer-handle] {
        position: absolute;
        top: .5rem;
        justify-self: center;
        height: .25rem;
        width: 5rem;
        background: lightgrey;
        border-radius: 9999px;
    }

    [data-role=drawer-content] {
        overflow-y: scroll;
    }
</style>

@pushonce('scripts')
    <script>
        class Drawer {
            constructor (el) {
                this.el = el;

                this.setDefaults();
                this.getElements();
                this.setEvents();
            }

            /**
             * Set default values for the drawer.
             *
             * @return void
             */
            setDefaults() {
                this.velocity = 0;
                this.lastY = 0;
                this.lastTime = 0;
            }

            /**
             * Get the useful elements.
             *
             * @return void
             */
            getElements() {
                // All the elements that can open the drawer
                this.triggers = document.querySelectorAll(`[data-drawer="${this.el.id}"]`);

                // The dark background of the drawer
                this.background = this.el.querySelector('[data-role=drawer-background]');

                // The draggable part of the drawer
                this.container = this.el.querySelector('[data-role=drawer-container]');
            }

            /**
             * Set the events.
             *
             * @return void
             */
            setEvents() {
                // Open drawer
                this.triggers.forEach((trigger) => trigger.addEventListener('click', this.handleOpen.bind(this)));

                // Close drawer
                this.background.addEventListener('click', this.handleClose.bind(this));

                // Drag events (for mouse and touch)
                // Mouse
                this.container.addEventListener("mousedown", this.dragStart.bind(this));
                document.addEventListener("mousemove", this.dragging.bind(this));
                document.addEventListener("mouseup", this.dragStop.bind(this));

                //Touch
                this.container.addEventListener("touchstart", this.dragStart.bind(this));
                document.addEventListener("touchmove", this.dragging.bind(this));
                document.addEventListener("touchend", this.dragStop.bind(this));
            }

            /**
             * Handle drawer opening.
             *
             * @return void
             */
            handleOpen(e) {
                e.preventDefault();

                // Block the scroll on the body
                this.style(document.body, {
                    position: 'fixed',
                    overflow: 'hidden',
                })

                // Add open styles
                this.el.style.display = 'block';
                this.style(this.container, {
                    transform: 'translateY(100%)',
                    transition: '200ms transform ease-out',
                });
                this.style(this.background, {
                    opacity: 0,
                    transition: '200ms transform ease-out',
                });

                // Wait 10ms to avoid bug with transition between diplay none and block
                setTimeout(function () {
                    this.container.style.transform = 'translateY(0)';
                    this.background.style.opacity = 1;
                }.bind(this), 10)
            }

            /**
             * Handle the drawer closing.
             *
             * @return void
             */
            handleClose(e) {
                e.preventDefault();

                // Reset the velocity
                this.velocity = 0;

                // Add closed styles
                this.container.style.transform = 'translateY(100%)';
                this.background.style.opacity = 0;

                // Wait for the animation to finish before making the element display none
                setTimeout(function () {
                    this.el.style.display = 'none';
                    document.body.classList.remove('bodyblock');
                }.bind(this), 400)
            }

            /**
             * Handle what happens when the user starts dragging
             *
             * @return void
             */
            dragStart(e) {
                this.isDragging = true;

                // Avoid delay when dragging
                this.container.style.transition = '0ms';

                // Detect the Y value where the user clicked
                this.startY = e.pageY || e.touches?.[0].pageY;

                // Set lastY and lastTime as default values to calculate velocity
                this.lastY = this.startY;
                this.lastTime = Date.now();
            }


            /**
             * Handle what happens when the user is dragging
             *
             * @return void
             */
            dragging(e) {
                // Only drag if the element shouldn't be scrolled
                if (!this.isDragging) {
                    return;
                }

                // Set the new values used to calculate position and velocity
                const newY = e.clientY || e.touches?.[0].pageY;
                const currentTime = Date.now();
                const deltaY = this.startY - newY;


                if (newY > this.startY) {
                    // Apply dragging based on if the element should drag or scroll
                    if (!this.shouldDrag(e.target, 'down')) {
                        this.isDragging = false;
                        return;
                    }

                    // Slide the container down based on mouse/figer position
                    this.container.style.transform = `translateY(${-1 * deltaY}px)`;
                } else {
                    // Apply dragging based on if the element should drag or scroll
                    if (!this.shouldDrag(e.target, 'up')) {
                        this.isDragging = false;
                        return;
                    }

                    // Reduce the amount of drag exponentially as the user goes above the startY
                    const adjustedDeltaY = -20 * (Math.log(deltaY + 11) - 2);
                    this.container.style.transform = `translateY(${adjustedDeltaY}px)`;
                }

                // Calculate velocity
                const distance = this.lastY - newY;
                const time = currentTime - this.lastTime;
                this.velocity = distance / time;

                // Adjust lastY and lastTime based on current values
                this.lastY = newY;
                this.lastTime = currentTime;
            }


            /**
             * Handle what happens when the user stops dragging
             *
             * @return void
             */
            dragStop(e) {
                this.isDragging = false;

                // Reset the transform styles
                this.container.style.transition = '200ms transform ease-out';

                const currentTop = this.container.getBoundingClientRect().top;

                if (currentTop > window.innerHeight / 2 || -this.velocity > 0.5) {
                    // If the user is bellow half of the screen or has flicked down, close the drawer
                    this.handleClose(e);
                } else {
                    // If the user is above half of the window, go back to initial position
                    this.container.style.transform = 'translateY(0)';
                }
            }

            /**
             * Determine if the user should be allowed to drag
             *
             * @param {HTMLElement} target
             * @param {string} direction
             *
             * @return bool
             */
            shouldDrag(target, direction) {
                this.target = target;
                // Loop through the target all the way up to the content container
                while (this.target && this.target !== this.content) {
                    // Check if the element is scrollable
                    if (this.target.scrollHeight > this.target.clientHeight) {
                        // If you have reached the top and are trying to scroll more, drag down
                        if (direction === 'down' && this.target.scrollTop === 0) {
                            // Prevent all scrollable elements from overscrolling towards the top
                            this.handleOverscroll(this.target, 'none');
                            return true;
                        }

                        // Allow all scrollable elements to overscroll towards the bottom
                        this.handleOverscroll(this.target, 'auto');

                        return false;
                    }
                    // Make the parent of the target the current target
                    this.target = this.target.parentNode;
                }

                // Default to allowing drag
                return true;
            }

            /**
             * Handle overscroll behavior for all scrollable elements until content container
             *
             * @param {HTMLElement} current
             * @param {string} value
             *
             * @return void
             */
            handleOverscroll(current, value) {
                while (current && current !== this.content && current.scrollHeight > current.clientHeight) {
                    current.style.overscrollBehavior = value;
                    current = current.parentNode;
                }
            }

            /**
             * Apply multiple css properties to an element at once
             *
             * @param {HTMLElement} element
             * @param {Object} styles
             *
             * @return void
             */
            style(element, styles) {
                for (const [key, value] of Object.entries(styles)) {
                    element.style[key] = value;
                }
            }
        }

        /**
         * Setup the drawer logic for each drawer on the page
         */
        document.querySelectorAll('[data-role=drawer]').forEach(el => {
            new Drawer(el);
        });
    </script>
@endpushonce
